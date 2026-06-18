<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHrAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasHrAccess()) {
            if ($request->expectsJson()) {
                abort(403, 'دسترسی منابع انسانی مورد نیاز است.');
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
