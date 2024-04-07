<?php

namespace App;

use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait HandlesMessageLimits
{
    public function getUserMessagesFromLastDay()
    {
        $now = Carbon::now();

        $oneDayAgo = $now->copy()->subDay();

        $maxRequests = Auth::user()->max_requests;

        return Message::query()
            ->join(
                'conversations',
                'messages.conversation_id',
                '=',
                'conversations.id'
            )
            ->where('conversations.user_id', '=', Auth::id())
            ->whereBetween('messages.created_at', [$oneDayAgo, $now])
            ->orderBy('messages.created_at', 'desc')
            // It's important to limit the query by 'maxRequests' to avoid inconsistency
            // in the error message if the user's 'max_requests' value is set to a lower
            // value in production.
            ->limit($maxRequests)
            ->get(['messages.created_at'])
            ->reverse();
    }

    public function checkRemainingMessages()
    {
        $maxRequests = Auth::user()->max_requests;
        $remainingMessagesAlertLevels = config(
            'chat.remaining_requests_alert_levels'
        );

        $messages = $this->getUserMessagesFromLastDay();

        $remainingMessagesCount = $maxRequests - $messages->count();

        if (in_array($remainingMessagesCount, $remainingMessagesAlertLevels)) {
            return "You have $remainingMessagesCount messages remaining for today.";
        }

        return false;
    }
}
