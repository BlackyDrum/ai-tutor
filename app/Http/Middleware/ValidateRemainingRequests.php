<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ChatController;
use App\Models\Messages;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $maxRequests = Auth::user()->max_requests;

        $messages = ChatController::getUserMessagesFromLastDay();

        if ($messages->count() >= $maxRequests) {
            $firstMessageTime = $messages->first()->created_at;

            $nextAvailableTime = Carbon::parse($firstMessageTime)->addDay();

            $hoursUntilNextAvailableTime = (int) Carbon::now()->diffInHours(
                $nextAvailableTime
            );

            Log::info('App: Daily limit reached for user with ID {user-id}', [
                'next-available-message' => $nextAvailableTime,
                'max_requests' => Auth::user()->max_requests,
            ]);

            return response()->json(
                [
                    'message' => "Daily limit reached. Try again in $hoursUntilNextAvailableTime hours. ($nextAvailableTime)",
                ],
                429
            );
        }

        return $next($request);
    }
}
