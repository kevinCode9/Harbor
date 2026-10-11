<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_auth')) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['error' => 'Unauthenticated', 'redirect' => route('admin.login')], 401);
            }
            return redirect()->route('admin.login');
        }

        // Keep last_active_at fresh for sub-admin online tracking (throttled to once per 30 seconds).
        if (session('admin_role') === 'sub' && ($id = session('sub_admin_id'))) {
            $key = '_sub_active_ts';
            if (!session($key) || (time() - session($key)) >= 30) {
                session([$key => time()]);
                \App\Models\SubAdminAccount::where('id', $id)->update(['last_active_at' => now()]);
            }
        }

        // Heartbeat master admin presence (throttled to once per 20 seconds).
        if (session('admin_role') === 'master') {
            $hbKey = '_master_hb_ts';
            if (!session($hbKey) || (time() - session($hbKey)) >= 20) {
                session([$hbKey => time()]);
                $sid = session()->getId();
                $exists = \Illuminate\Support\Facades\DB::table('admin_online_sessions')
                    ->where('session_id', $sid)->exists();
                // Persist login time in session so it survives across requests
                if (!session('_admin_logged_in_at')) {
                    session(['_admin_logged_in_at' => now()->toDateTimeString()]);
                }
                $loggedInAt = session('_admin_logged_in_at');

                if ($exists) {
                    \Illuminate\Support\Facades\DB::table('admin_online_sessions')
                        ->where('session_id', $sid)
                        ->update([
                            'ip_address'   => $request->ip(),
                            'user_agent'   => substr($request->userAgent() ?? '', 0, 500),
                            'last_seen_at' => now(),
                        ]);
                } else {
                    \Illuminate\Support\Facades\DB::table('admin_online_sessions')->insert([
                        'session_id'   => $sid,
                        'ip_address'   => $request->ip(),
                        'user_agent'   => substr($request->userAgent() ?? '', 0, 500),
                        'last_seen_at' => now(),
                        'logged_in_at' => $loggedInAt,
                    ]);
                }
            }
        }

        return $next($request);
    }
}
