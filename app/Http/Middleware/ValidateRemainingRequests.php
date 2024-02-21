<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ChatController;
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
        $maxRequests = config('api.max_requests');

        $messages = ChatController::getUserMessagesFromLastDay();

        if ($messages->count() >= $maxRequests) {
            $firstMessageTime = $messages->reverse()->first()->created_at;

            $nextAvailableTime = Carbon::parse($firstMessageTime)->addDay();

            $hoursUntilNextAvailableTime = Carbon::now()->diffInHours($nextAvailableTime, false);

            return response()->json(['message' => "Daily limit reached. Try again in $hoursUntilNextAvailableTime hours. ($nextAvailableTime)"], 429);
        }

        return $next($request);
    }
}
