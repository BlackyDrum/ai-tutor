<?php

namespace App\Http\Middleware;

use App\Models\Messages;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateRemainingRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $now = Carbon::now();

        $oneDayAgo = $now->copy()->subDay();

        $maxRequests = config('api.max_requests');

        $messages = Messages::query()
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', '=', Auth::id())
            ->whereBetween('messages.created_at', [$oneDayAgo, $now])
            ->orderBy('messages.created_at', 'desc')
            // It's important to limit the query by 'maxRequests' to avoid inconsistency
            // in the error message if 'api.max_requests' is set to a lower value in production.
            ->limit($maxRequests)
            ->get(['messages.created_at']);

        if ($messages->count() >= $maxRequests) {
            $firstMessageTime = $messages->reverse()->first()->created_at;

            $nextAvailableTime = Carbon::parse($firstMessageTime)->addDay();

            $hoursUntilNextAvailableTime = Carbon::now()->diffInHours($nextAvailableTime, false);

            return response()->json(['message' => "Daily limit reached. Try again in $hoursUntilNextAvailableTime hours. ($nextAvailableTime)"], 429);
        }

        return $next($request);
    }
}
