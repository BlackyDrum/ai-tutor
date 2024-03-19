<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAcceptedTerms
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::user()->terms_accepted_at) {
            Log::info(
                'App: User with ID {user-id} tried to access protected routes while terms are not accepted'
            );

            abort(403, 'Terms not accepted');
        }

        return $next($request);
    }
}
