<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\Conversations;
use App\Models\Messages;
use App\Rules\ValidateConversationOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function show(string $id)
    {
        $conversation = Conversations::query()
            ->where('api_id', $id)
            ->first();

        if (empty($conversation) || $conversation->user_id !== Auth::id()) {
            return redirect('/');
        }

        $messages = Messages::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:' . config('api.max_message_length'),
            'collection' => 'required|integer|exists:collections,id',
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()],
        ]);

        $collection = Collections::query()->find($request->input('collection'))->name;

        $pastMessages = Messages::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.api_id', '=', $request->input('conversation_id'))
            ->select(['messages.user_message'])
            ->orderBy('messages.created_at')
            ->get();

        // We use a transaction here allowing rollback of the rows created
        // in 'createPromptWithContext' if something fails
        DB::beginTransaction();

        try {
            $promptWithContext =
                ChromaController::createPromptWithContext($collection, $request->input('message'), $request->input('conversation_id'), $pastMessages);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => 'Internal Server Error'], 500);
        }

        $token = HomeController::getBearerToken();

        if (is_array($token)) {
            DB::rollBack();
            return response()->json($token['reason'], intval($token['status']));
        }

        $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $request->input('conversation_id'),
            'message' => $promptWithContext,
        ]);

        if ($response->failed()) {
            DB::rollBack();
            return response()->json($response->reason(), $response->status());
        }

        $conversation = Conversations::query()
            ->where('api_id', $request->input('conversation_id'))
            ->first();

        $message = Messages::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => htmlspecialchars($response->json()['response']),
            'conversation_id' => $conversation->id,
        ]);

        DB::commit();

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
