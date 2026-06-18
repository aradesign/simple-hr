<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canManageSettings()) {
            abort(403, 'فقط مدیر سیستم به این بخش دسترسی دارد.');
        }

        return $next($request);
    }
}
