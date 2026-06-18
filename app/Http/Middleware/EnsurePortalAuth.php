<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAuth
{
    public const SESSION_KEY = 'portal_person_id';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('portal.login');
        }

        return $next($request);
    }
}
