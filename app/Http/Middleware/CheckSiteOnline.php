<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LiveledSettings;

class CheckSiteOnline
{
    public function handle(Request $request, Closure $next)
    {
        $settings = LiveledSettings::get();
        if (!($settings['site_online'] ?? true)) {
            // Render offline view directly — no redirect, so /offline itself stays safe
            return response(view('user.offline', [
                'token' => $request->cookie('liveled_token'),
                'page'  => 'offline',
            ]), 503);
        }
        return $next($request);
    }
}
