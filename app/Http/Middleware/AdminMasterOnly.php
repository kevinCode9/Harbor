<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMasterOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = session('admin_role');
        // Missing role means the session predates the sub-admin feature — treat as master.
        if ($role !== null && $role !== 'master') {
            abort(403, 'Access restricted.');
        }

        return $next($request);
    }
}
