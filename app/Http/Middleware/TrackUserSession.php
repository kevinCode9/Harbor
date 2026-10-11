<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // True start of this HTTP request (PHP request-entry clock), captured
        // before session resolution, firstOrCreate, locking, render, or persist.
        $requestStartUs = (int) round(((float) ($request->server->get('REQUEST_TIME_FLOAT') ?: microtime(true))) * 1_000_000);

        // Skip tracking for admin pages
        if (session('admin_auth') && strpos($request->path(), 'admin') === 0) {
            return $next($request);
        }

        // Skip session creation/tracking for admin previews — prevents fake sessions appearing in the panel
        if ($request->has('_liveled_preview') && session('admin_auth')) {
            return $next($request);
        }

        $token = $request->cookie('liveled_token');

        if (!$token) {
            $token = \Illuminate\Support\Str::random(48);
        }

        $path        = $request->path();
        $currentPage = \App\Services\DocumentAuthority::canonicalPage($path);

        // Pages that require an existing session — a fresh/re-created session
        // on these pages means the user was deleted and came back mid-flow.
        // Deep pages — fresh sessions here mean user jumped mid-flow (bookmarked, refreshed after delete).
        // Redirect them to the configured entry page, UNLESS this page IS the entry page.
        $deepPages  = [];
        $entryPage  = ltrim(\App\Services\LiveledSettings::getEntryPage(), '/');

        // Define cookie helper once — used both for normal response and for deep-page redirects
        $makeCookie = fn() => cookie()->forever('liveled_token', $token, '/', null, false, true, false, 'lax');

        try {
            $siteOnline = (bool) (\App\Services\LiveledSettings::get()['site_online'] ?? true);

            $existingSession = \App\Models\UserSession::where('session_token', $token)->first();

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
                if ($path === $entryPage) {
                    $tombstone->delete();
                } else {
                    $exitUrl = (string) (($tombstone->payload['url'] ?? '') ?: '/offline');
                    $exitPath = parse_url($exitUrl, PHP_URL_PATH);
                    if (!is_string($exitPath) || $exitPath === '') {
                        $exitPath = strtok($exitUrl, '?') ?: $exitUrl;
                    }
                    $exitPath = ltrim((string) $exitPath, '/');
                    if ($path === $exitPath) {
                        return $next($request)->withCookie($makeCookie());
                    }
                    return redirect($exitUrl)->withCookie($makeCookie());
                }
            }

            // When offline, do not create new sessions — only track existing ones
            if (!$siteOnline && !$existingSession) {
                return $next($request)->withCookie($makeCookie());
            }

            $session = \App\Models\UserSession::firstOrCreate(
                ['session_token' => $token],
                [
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->userAgent(),
                    'current_page' => $currentPage,
                    'last_seen_at' => now(),
                ]
            );

            if ($session->wasRecentlyCreated) {
                foreach (\App\Models\AdminCommand::where('session_token', $token)->get() as $cmd) {
                    $pl = is_array($cmd->payload) ? $cmd->payload : [];
                    $keep = $cmd->command === 'redirect'
                        && !empty($pl['end_session'])
                        && $cmd->created_at && $cmd->created_at->gte(now()->subHours(2));
                    if (!$keep) {
                        $cmd->delete();
                    }
                }

                // Only redirect if this page is a deep page AND it is not the configured entry page
                if (in_array($path, $deepPages) && $path !== $entryPage) {
                    return redirect('/' . $entryPage)->withCookie($makeCookie());
                }
            }

            $committedDoc = \App\Services\DocumentAuthority::mint($session, $currentPage, $requestStartUs);
            if (is_string($committedDoc) && $committedDoc !== '') {
                $request->attributes->set('ll_document_id', $committedDoc);
            }
        } catch (\Exception $e) {
            // DB unavailable — still serve the page with the cookie set.
            // Do not bake an uncommitted document_id into HTML.
        }

        // Cookie is always set regardless of DB outcome — token must be stable across requests
        return $next($request)->withCookie($makeCookie());
    }
}
