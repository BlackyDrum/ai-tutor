<?php

namespace App\Http\Middleware;

use App\Models\Blacklist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBlacklist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $blacklisted = Blacklist::query()
            ->where('abbreviation', '=', Auth::user()->abbreviation)
            ->first();

        if ($blacklisted) {
            Auth::logout();

            return redirect('/login');
        }

        return $next($request);
    }
}
