<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasActiveWorkspace(), 403, __('app.account_unavailable'));

        return $next($request);
    }
}
