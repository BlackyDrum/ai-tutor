<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ShareLoggingContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::shareContext([
            'request-id' => (string) Str::uuid(),
            'user-id' => Auth::id() ?? null,
            'username' => Auth::user()?->name ?? null,
            'ip-address' => $request->getClientIp() ?? null,
            'route' => $request->route()?->uri ?? null,
            'method' => $request->method() ?? null,
        ]);

        return $next($request);
    }
}
