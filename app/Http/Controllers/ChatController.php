<?php

namespace App\Http\Controllers;

use App\Models\Agents;
use App\Models\Collections;
use App\Models\ConversationHasDocument;
use App\Models\Conversations;
use App\Models\Messages;
use App\Models\Modules;
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
            ->where('url_id', $id)
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

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $conversation->name,
            'hasPrompt' => true,
            'username' => null,
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:' . config('chat.max_message_length'),
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,url_id', new ValidateConversationOwner()],
        ]);

        if (!Auth::user()->module_id) {
            Log::warning('App: User with ID {user-id} is not associated with a module');

            return response()->json('You are not associated with a module. Try to login again.',500);
        }

        $module = Modules::query()->find(Auth::user()->module_id);

        $agent = Agents::query()
            ->where('module_id', '=', $module->id)
            ->where('active', '=', true)
            ->first();

        $collection = Collections::query()
            ->where('module_id', '=', Auth::user()->module_id)
            ->first();

        if (!$collection) {
            Log::critical('App: Failed to find a collection for module with ID {module-id}', [
                'module-id' => Auth::user()->module_id
            ]);

            return response()->json('Internal Server Error.',500);
        }

        $now = Carbon::now();

        // We use a transaction here in case the following API request
        // or ChromaDB item retrieval fails, allowing us to roll back
        // the database changes
        DB::beginTransaction();

        $conversation = Conversations::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->first();

        $messages = Messages::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->limit(config('chat.max_messages_included'))
            ->get()
            ->reverse();

        // If older messages (and therefore embeddings/documents) leave the context window,
        // we delete the entries from the 'conversation_has_documents' table, so that
        // they can potentially be embedded in the context once again
        if ($messages->isNotEmpty()) {
            ConversationHasDocument::query()
                ->where('conversation_id', '=', $conversation->id)
                ->where('created_at', '<', $messages->first()->created_at)
                ->delete();
        }

        $recentMessages = [];

        foreach ($messages as $message) {
            $recentMessages[] = [
                'role' => 'user',
                'content' => $message->user_message_with_context,
            ];

            $recentMessages[] = [
                'role' => 'assistant',
                'content' => htmlspecialchars_decode($message->agent_message),
            ];
        }

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

        $response = self::sendMessageToOpenAI($agent->instructions, $promptWithContext, $recentMessages);

        if ($response->failed()) {
            Log::error('OpenAI: Failed to send message. Reason: {reason}. Status: {status}', [
                'reason' => $response->reason(),
                'status' => $response->status(),
                'conversation-id' => $request->input('conversation_id'),
            ]);

            DB::rollBack();
            return response()->json($response->reason(), $response->status());
        }

        $message = Messages::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => htmlspecialchars($response->json()['choices'][0]['message']['content']),
            'user_message_with_context' => $promptWithContext,
            'prompt_tokens' => $response->json()['usage']['prompt_tokens'],
            'completion_tokens' => $response->json()['usage']['completion_tokens'],
            'conversation_id' => $conversation->id,
            'created_at' => $now
        ]);

        DB::commit();

        $maxRequests = Auth::user()->max_requests;
        $remainingMessagesAlertLevels  = config('chat.remaining_requests_alert_levels');

        $messages = self::getUserMessagesFromLastDay();

        $remainingMessagesCount = $maxRequests - $messages->count();

        if (in_array($remainingMessagesCount, $remainingMessagesAlertLevels)) {
            $message['info'] = "You have $remainingMessagesCount messages remaining for today.";
        }

        return response()->json($message);
    }

    public static function sendMessageToOpenAI($systemMessage, $userMessage, $recentMessages = null)
    {
        $token = config('api.openai_api_key');

        $messages = [
            ['role' => 'system', 'content' => $systemMessage]
        ];

        if ($recentMessages) {
            $messages = array_merge($messages, $recentMessages);
        }

        $userMessage =
            "Use the context from this or from previous messages to answer the user's question.\n\n" . $userMessage;

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return Http::withToken($token)->post('https://api.openai.com/v1/chat/completions', [
            'model' => config('api.openai_language_model'),
            'temperature' => (float)Auth::user()->temperature,
            'max_tokens' => (int)Auth::user()->max_tokens,
            'messages' => $messages
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
