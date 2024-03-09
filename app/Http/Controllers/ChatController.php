<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\Conversations;
use App\Models\Messages;
use App\Models\SharedConversations;
use App\Rules\ValidateConversationOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function show(string $id)
    {
        $conversation = Conversations::query()
            ->where('api_id', $id)
            ->first();

        if (empty($conversation) || $conversation->user_id !== Auth::id()) {
            Log::info('App: User with ID {user-id} tried to access an invalid conversation', [
                'conversation-id' => $id
            ]);

            return redirect('/');
        }

        $messages = Messages::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at')
            ->get();

        Log::info('App: User with ID {user-id} accessed a conversation', [
            'conversation-id' => $id
        ]);

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $conversation->name,
            'hasPrompt' => true,
        ]);
    }

    public function share(string $id) {
        $shared = SharedConversations::query()
            ->where('shared_conversations.url_identifier', '=', $id)
            ->first();

        if (!$shared) {
            Log::info('App: User with ID {user-id} tried to access an invalid, shared conversation', [
                'shared-conversation-url-id' => $id
            ]);

            return redirect('/');
        }

        $name = Conversations::query()
            ->find($shared->conversation_id)
            ->name;

        $messages = SharedConversations::query()
            ->join('conversations', 'conversations.id', '=', 'shared_conversations.conversation_id')
            ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
            ->where('shared_conversations.url_identifier', '=', $id)
            ->whereRaw('messages.created_at < shared_conversations.created_at')
            ->select([
                'messages.user_message',
                'messages.agent_message',
            ])
            ->get();

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $name,
            'hasPrompt' => false,
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:' . config('api.max_message_length'),
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()],
        ]);

        Log::info('App: User with ID {user-id} is trying to send a message in conversation with ID {conversation-id}', [
            'message' => $request->input('message'),
            'conversation-id' => $request->input('conversation_id')
        ]);

        if (!Auth::user()->module_id) {
            Log::warning('App: User with ID {user-id} is not associated with a module');

            return response()->json('You are not associated with a module. Try to login again.',500);
        }

        $collection = Collections::query()
            ->where('module_id', '=', Auth::user()->module_id)
            ->first();

        if (!$collection) {
            Log::critical('App: Failed to find a collection for module with ID {module-id}', [
                'module-id' => Auth::user()->module_id
            ]);

            return response()->json('Internal Server Error.',500);
        }

        // We use a transaction here in case the following API requests
        // fail, allowing us to rollback the rows created in
        // 'ChromaController::createPromptWithContext()'
        DB::beginTransaction();

        try {
            $promptWithContext =
                ChromaController::createPromptWithContext($collection->name, $request->input('message'), $request->input('conversation_id'));
        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to create prompt with context. Reason: {message}', [
                'message' => $exception->getMessage(),
                'collection' => $collection->name,
                'conversation-id' => $request->input('conversation_id')
            ]);

            DB::rollBack();
            return response()->json(['message' => 'Internal Server Error'], 500);
        }

        $token = HomeController::getBearerToken();

        if (is_array($token)) {
            DB::rollBack();
            return response()->json($token['reason'], $token['status']);
        }

        try {
            $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
                'conversation_id' => $request->input('conversation_id'),
                'message' => $promptWithContext,
            ]);
        } catch (\Exception $exception) {
            Log::error('App/ConversAItion: Failed to send message. Reason: {message}', [
                'message' => $exception->getMessage(),
                'conversation-id' => $request->input('conversation_id'),
            ]);

            DB::rollBack();
            return response()->json('Internal Server Error', '500');
        }

        if ($response->failed()) {
            Log::error('ConversAItion: Failed to send message. Reason: {reason}. Status: {status}', [
                'reason' => $response->reason(),
                'status' => $response->status(),
                'conversation-id' => $request->input('conversation_id'),
            ]);

            DB::rollBack();
            return response()->json($response->reason(), $response->status());
        }

        $conversation = Conversations::query()
            ->where('api_id', '=', $request->input('conversation_id'))
            ->first();

        $message = Messages::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => htmlspecialchars($response->json()['response']),
            'conversation_id' => $conversation->id,
        ]);

        Log::info('App: User with ID {user-id} sent a new message in conversation with ID {conversation-id}', [
            'conversation-id' => $request->input('conversation_id'),
        ]);

        DB::commit();

        $maxRequests = Auth::user()->max_requests;
        $remainingMessagesAlertLevels  = config('api.remaining_requests_alert_levels');

        $messages = self::getUserMessagesFromLastDay();

        $remainingMessagesCount = $maxRequests - $messages->count();

        if (in_array($remainingMessagesCount, $remainingMessagesAlertLevels)) {
            $message['info'] = "You have $remainingMessagesCount messages remaining for today.";
        }

        return response()->json($message);
    }

    public function createShare(Request $request)
    {
        $request->validate([
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()]
        ]);

        $conversation = Conversations::query()
            ->where('api_id', '=', $request->input('conversation_id'))
            ->first();

        $sharedConversation = SharedConversations::query()
            ->where('conversation_id', '=', $conversation->id)
            ->first();

        if ($sharedConversation) {
            return response()->json(['message' => 'You have shared this conversation already'], 409);
        }

        $sharedConversation = SharedConversations::query()->create([
            'url_identifier' => Str::random(40),
            'conversation_id' => $conversation->id,
        ]);

        Log::info('User with ID {user-id} shared a conversation', [
            'url_identifier' => $sharedConversation->url_identifier,
            'conversation_id' => $conversation->id,
        ]);

        return response()->json(['url_identifier' => $sharedConversation->url_identifier]);
    }

    public function deleteShare(Request $request)
    {
        $request->validate([
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()]
        ]);

        $conversation = Conversations::query()
            ->where('api_id', '=', $request->input('conversation_id'))
            ->first();

        SharedConversations::query()
            ->where('conversation_id', '=', $conversation->id)
            ->delete();

        Log::info('User with ID {user-id} deleted a shared conversation', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public static function getUserMessagesFromLastDay()
    {
        $now = Carbon::now();

        $oneDayAgo = $now->copy()->subDay();

        $maxRequests = Auth::user()->max_requests;

        return Messages::query()
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.user_id', '=', Auth::id())
            ->whereBetween('messages.created_at', [$oneDayAgo, $now])
            ->orderBy('messages.created_at', 'desc')
            // It's important to limit the query by 'maxRequests' to avoid inconsistency
            // in the error message if the user's 'max_requests' value is set to a lower
            // value in production.
            ->limit($maxRequests)
            ->get(['messages.created_at']);
    }
}
