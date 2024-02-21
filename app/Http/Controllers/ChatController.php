<?php

namespace App\Http\Controllers;

use App\Models\Conversations;
use App\Models\Messages;
use App\Rules\ValidateConversationOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function show(string $id)
    {
        $conversation = Conversations::query()->find($id);

        if (empty($conversation) || $conversation->user_id !== Auth::id()) {
            return redirect('/');
        }

        $messages = Messages::query()->where('conversation_id', '=', $id)->orderBy('created_at')->get();

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:1',
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,id', new ValidateConversationOwner()],
        ]);

        $token = HomeController::getBearerToken();

        if (is_array($token)) {
            return response()->json($token['reason'], intval($token['status']));
        }

        $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $request->input('conversation_id'),
            'message' => $request->input('message'),
        ]);

        if ($response->failed()) {
            return response()->json($response->reason(), $response->status());
        }

        $message = Messages::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => htmlspecialchars($response->json()['response']),
            'conversation_id' => $request->input('conversation_id'),
        ]);

        $maxRequests = config('api.max_requests');
        $remainingMessagesAlertLevels  = config('api.remaining_requests_alert_levels');

        $messages = self::getUserMessagesFromLastDay();

        $remainingMessagesCount = $maxRequests - $messages->count();

        if (in_array($remainingMessagesCount, $remainingMessagesAlertLevels)) {
            $message['info'] = "You have $remainingMessagesCount messages remaining for today.";
        }

        return response()->json($message);
    }

    public static function getUserMessagesFromLastDay()
    {
        $now = Carbon::now();

        $oneDayAgo = $now->copy()->subDay();

        $maxRequests = config('api.max_requests');

        return Messages::query()
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', '=', Auth::id())
            ->whereBetween('messages.created_at', [$oneDayAgo, $now])
            ->orderBy('messages.created_at', 'desc')
            // It's important to limit the query by 'maxRequests' to avoid inconsistency
            // in the error message if 'api.max_requests' is set to a lower value in production.
            ->limit($maxRequests)
            ->get(['messages.created_at']);
    }
}
