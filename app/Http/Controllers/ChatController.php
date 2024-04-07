<?php

namespace App\Http\Controllers;

use App\AppSupportTraits;
use App\Models\Collection;
use App\Models\ConversationHasDocument;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\Message;
use App\Models\Module;
use App\Models\SharedConversation;
use App\OpenAICommunication;
use App\Rules\ValidateConversationOwner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class ChatController extends Controller
{
    use OpenAICommunication, AppSupportTraits;

    public function show(string $id)
    {
        $conversation = Conversation::query()->where('url_id', $id)->first();

        $messages = self::getMessagesForChat($id);

        if (!$messages) {
            return redirect('/');
        }

        $date = Document::query()
            ->where('collection_id', '=', $conversation->collection_id)
            ->orderBy('updated_at', 'desc')
            ->first()
            ->updated_at ?? null;

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $conversation->name,
            'hasPrompt' => true,
            'showOptions' => true,
            'username' => null,
            'info' => session()->pull('info_message_remaining_messages'),
            'current_module' => Module::query()->find($conversation->module_id)->name,
            'data_from' => $date
        ]);
    }

    public function fetchMessagesForChat(
        Request $request,
        string $conversation_id
    ) {
        $messages = self::getMessagesForChat($conversation_id);

        if (!$messages) {
            return response()->json(
                ['message' => 'The selected conversation id is invalid'],
                422
            );
        }

        return response()->json($messages);
    }

    public function fetchMessagesForPeek(
        Request $request,
        string $conversation_id
    ) {
        $messages = self::getMessagesForPeek($conversation_id);

        if (!$messages) {
            return response()->json(
                ['message' => 'The selected conversation id is invalid'],
                422
            );
        }

        return response()->json($messages);
    }

    public function fetchMessagesForShare(
        Request $request,
        string $conversation_id
    ) {
        $messages = self::getMessagesForShare($conversation_id);

        if (!$messages) {
            return response()->json(
                ['message' => 'The selected conversation id is invalid'],
                422
            );
        }

        return response()->json($messages);
    }

    public static function getMessagesForShare($conversation_id)
    {
        $shared = SharedConversation::query()
            ->where('shared_conversations.shared_url_id', '=', $conversation_id)
            ->first();

        if (!$shared) {
            return false;
        }

        $conversation = Conversation::query()->find($shared->conversation_id);

        return SharedConversation::query()
            ->join(
                'conversations',
                'conversations.id',
                '=',
                'shared_conversations.conversation_id'
            )
            ->join(
                'messages',
                'messages.conversation_id',
                '=',
                'conversations.id'
            )
            ->where('conversations.id', '=', $conversation->id)
            ->whereRaw('messages.created_at < shared_conversations.created_at')
            ->orderBy('messages.created_at', 'desc')
            ->select(['messages.user_message', 'messages.agent_message'])
            ->paginate(
                self::isMobile(\request()->userAgent())
                    ? config('chat.messages_per_page_mobile')
                    : config('chat.messages_per_page_desktop')
            );
    }

    public static function getMessagesForPeek($conversation_id)
    {
        $conversation = Conversation::query()
            ->where('url_id', $conversation_id)
            ->first();

        if (!$conversation) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->paginate(
                self::isMobile(\request()->userAgent())
                    ? config('chat.messages_per_page_mobile')
                    : config('chat.messages_per_page_desktop')
            );
    }

    public static function getMessagesForChat($conversation_id)
    {
        $conversation = Conversation::query()
            ->where('url_id', $conversation_id)
            ->first();

        if (!$conversation || $conversation->user_id !== Auth::id()) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->select(['id', 'user_message', 'agent_message', 'helpful'])
            ->paginate(
                self::isMobile(\request()->userAgent())
                    ? config('chat.messages_per_page_mobile')
                    : config('chat.messages_per_page_desktop')
            );
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' =>
                'required|string|max:' . config('chat.max_message_length'),
            'conversation_id' => [
                'bail',
                'required',
                'string',
                'exists:conversations,url_id',
                new ValidateConversationOwner(),
            ],
        ]);

        $conversation = Conversation::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->first();

        $appCheckResults = $this->validateAppFunctionality($conversation);

        if (!$appCheckResults) {
            return response()->json(
                ['message' => 'Internal Server Error'],
                500
            );
        }

        $agent = $appCheckResults['agent'];
        $collection = $appCheckResults['collection'];

        $now = Carbon::now();

        // We use a transaction here in case the following API request
        // or ChromaDB item retrieval fails, allowing us to roll back
        // the database changes
        DB::beginTransaction();

        $messages = Message::query()
            ->where('conversation_id', '=', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->limit($agent->max_messages_included)
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
            $promptWithContext = ChromaController::createPromptWithContext(
                $collection,
                $request->input('message'),
                $conversation
            );
        } catch (\Exception $exception) {
            Log::error(
                'ChromaDB: Failed to create prompt with context. Reason: {message}',
                [
                    'message' => $exception->getMessage(),
                    'collection' => $collection->name,
                    'conversation-id' => $conversation->id,
                ]
            );

            DB::rollBack();

            return response()->json(
                ['message' => 'Internal Server Error'],
                500
            );
        }

        $response = $this->sendMessageToOpenAI(
            systemMessage: $agent->instructions,
            userMessage: $promptWithContext,
            languageModel: $agent->openai_language_model,
            max_tokens: $agent->max_response_tokens,
            temperature: $agent->temperature,
            recentMessages: $recentMessages
        );

        if ($response->failed()) {
            Log::error(
                'OpenAI: Failed to send message. Reason: {reason}. Status: {status}',
                [
                    'reason' => $response->json()['error']['message'],
                    'status' => $response->status(),
                    'conversation-id' => $conversation->id,
                ]
            );

            DB::rollBack();

            return response()->json($response->reason(), $response->status());
        }

        $message = Message::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => htmlspecialchars(
                $response->json()['choices'][0]['message']['content']
            ),
            'user_message_with_context' => $promptWithContext,
            'prompt_tokens' => $response->json()['usage']['prompt_tokens'],
            'completion_tokens' => $response->json()['usage'][
                'completion_tokens'
            ],
            'openai_language_model' => $agent->openai_language_model,
            'conversation_id' => $conversation->id,
            'created_at' => $now,
        ]);

        DB::commit();

        $remaining = self::checkRemainingMessages();

        if ($remaining) {
            $message['info'] = $remaining;
        }

        return response()->json($message);
    }

    public static function checkRemainingMessages()
    {
        $maxRequests = Auth::user()->max_requests;
        $remainingMessagesAlertLevels = config(
            'chat.remaining_requests_alert_levels'
        );

        $messages = self::getUserMessagesFromLastDay();

        $remainingMessagesCount = $maxRequests - $messages->count();

        if (in_array($remainingMessagesCount, $remainingMessagesAlertLevels)) {
            return "You have $remainingMessagesCount messages remaining for today.";
        }

        return false;
    }

    public function updateRating(Request $request)
    {
        $request->validate([
            'helpful' => 'required|boolean',
            'message_id' => 'required|string',
        ]);

        $id = Hashids::decode($request->input('message_id'));

        try {
            Message::query()->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                ['message_id' => 'The selected message id is invalid'],
                404
            );
        }

        $message = Message::query()
            ->where('messages.id', '=', $id)
            ->join(
                'conversations',
                'conversations.id',
                '=',
                'messages.conversation_id'
            )
            ->where('conversations.user_id', '=', Auth::id())
            ->select(['messages.*'])
            ->first();

        if (!$message) {
            return response()->json(
                ['message' => 'The selected message id is invalid'],
                404
            );
        }

        $message->update([
            'helpful' => $request->input('helpful'),
        ]);

        return response()->json([
            'id' => $request->input('message_id'),
            'helpful' => $request->input('helpful'),
        ]);
    }

    public static function getUserMessagesFromLastDay()
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
}
