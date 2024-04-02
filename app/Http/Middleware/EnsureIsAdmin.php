<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !$request->user()->admin) {
            Log::warning(
                'App: User with ID {user-id} tried to access the admin area'
            );

            if($request->route()->getName() == 'peek.messages.fetch') {
                return \response()->json(['message' => 'Unauthorized'], 403);
            }

            return redirect('/');
        }

        return $next($request);
    }
}
