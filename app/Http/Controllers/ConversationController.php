<?php

namespace App\Http\Controllers;

use App\Models\Conversations;
use App\Models\Messages;
use App\Models\Modules;
use App\Models\SharedConversations;
use App\Models\User;
use App\Rules\ValidateConversationOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function createConversation(Request $request)
    {
        $request->validate([
            'message' =>
                'required|string|max:' . config('chat.max_message_length'),
        ]);

        $appCheckResults = HomeController::validateAppFunctionality();

        if (!$appCheckResults) {
            return response()->json(
                ['message' => 'Internal Server Error'],
                500
            );
        }

        $agent = $appCheckResults['agent'];
        $collection = $appCheckResults['collection'];
        $module = Modules::query()->find(Auth::user()->module_id);

        $count = Conversations::query()
            ->where('user_id', '=', Auth::id())
            ->count();

        // We use a transaction here in case something fails
        // to prevent us from having en empty conversation with
        // no messages
        DB::beginTransaction();

        $conversation = Conversations::query()->create([
            'name' => 'Chat #' . ($count + 1),
            'url_id' => Str::random(40),
            'agent_id' => $agent->id,
            'user_id' => Auth::id(),
            'module_id' => $module->id,
            'collection_id' => $collection->id,
        ]);

        // Get the current time and save it in the 'created_at' field for messages.
        // This is done before we add records to the 'conversation_has_document'
        // table in the 'createPromptWithContext' function. It's important because
        // it helps us know which message added which documents to the context window.
        // Later, if a message falls outside the context window, we can remove
        // the associated documents, too, so that they can potentially
        // be embedded in the context once again.
        $now = Carbon::now();

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

        $response = ChatController::sendMessageToOpenAI(
            $agent->instructions,
            $promptWithContext,
            $agent->openai_language_model,
            $agent->max_response_tokens,
            $agent->temperature
        );

        if ($response->failed()) {
            Log::error(
                'OpenAI: Failed to send message. Reason: {reason}. Status: {status}',
                [
                    'reason' => $response->json()['error']['message'],
                    'status' => $response->status(),
                ]
            );

            DB::rollBack();

            return response()->json($response->reason(), $response->status());
        }

        $systemMessage =
            'Create a concise and short title in 5 words or fewer for the messages. Focus on identifying and condensing the primary elements or topics discussed.';

        $agentResponse = [
            [
                'role' => 'assistant',
                'content' => $response->json()['choices'][0]['message'][
                    'content'
                ],
            ],
        ];

        $response2 = ChatController::sendMessageToOpenAI(
            $systemMessage,
            $request->input('message'),
            config('api.openai_conversation_title_creator_model'),
            32,
            0.8,
            $agentResponse,
            false
        );

        if ($response2->failed()) {
            Log::warning(
                'OpenAI: Failed to create conversation title. Reason: {reason}. Status: {status}',
                [
                    'reason' => $response2->json()['error']['message'],
                    'status' => $response2->status(),
                ]
            );
            // The error is just logged to monitor and troubleshoot issues. However, the failure does not stop or return an error to the user.
            // This decision is based on the assessment that this specific failure does not critically impact the overall functionality
            // of the conversation feature.
        } else {
            $conversation->update([
                'name' => $response2->json()['choices'][0]['message'][
                    'content'
                ],
            ]);
        }

        Messages::query()->create([
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

        $remaining = ChatController::checkRemainingMessages();

        if ($remaining) {
            session()->put('info_message_remaining_messages', $remaining);
        }

        Log::info('App: User with ID {user-id} created a new conversation', [
            'conversation-id' => $conversation->id,
        ]);

        return response()->json(['id' => $conversation->url_id]);
    }

    public function deleteConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => [
                'bail',
                'required',
                'string',
                'exists:conversations,url_id',
                new ValidateConversationOwner(),
            ],
        ]);

        Conversations::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->delete();

        Log::info(
            'App: User with ID {user-id} deleted a conversation with ID {conversation-id}',
            [
                'conversation-id' => $request->input('conversation_id'),
            ]
        );

        return response()->json(['id' => $request->input('conversation_id')]);
    }

    public function deleteAllConversations(Request $request)
    {
        Conversations::query()
            ->where('user_id', '=', Auth::id())
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function renameConversation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'conversation_id' => [
                'bail',
                'required',
                'string',
                'exists:conversations,url_id',
                new ValidateConversationOwner(),
            ],
        ]);

        Conversations::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->update([
                'name' => $request->input('name'),
            ]);

        Log::info(
            'App: User with ID {user-id} renamed a conversation with ID {conversation-id}',
            [
                'new-name' => $request->input('name'),
                'conversation-id' => $request->input('conversation_id'),
            ]
        );

        return response()->json([
            'name' => $request->input('name'),
            'id' => $request->input('conversation_id'),
        ]);
    }

    public function share(string $id)
    {
        $shared = SharedConversations::query()
            ->where('shared_conversations.shared_url_id', '=', $id)
            ->first();

        if (!$shared) {
            Log::info(
                'App: User with ID {user-id} tried to access an invalid, shared conversation',
                [
                    'shared-conversation-url-id' => $id,
                ]
            );

            return redirect('/');
        }

        $name = Conversations::query()->find($shared->conversation_id)->name;

        $messages = SharedConversations::query()
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
            ->where('shared_conversations.shared_url_id', '=', $id)
            ->whereRaw('messages.created_at < shared_conversations.created_at')
            ->orderBy('messages.created_at')
            ->select(['messages.user_message', 'messages.agent_message'])
            ->get();

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $name,
            'hasPrompt' => false,
            'showOptions' => false,
            'username' => null,
            'info' => null,
        ]);
    }

    public function createShare(Request $request)
    {
        $request->validate([
            'conversation_id' => [
                'bail',
                'required',
                'string',
                'exists:conversations,url_id',
                new ValidateConversationOwner(),
            ],
        ]);

        $conversation = Conversations::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->first();

        $sharedConversation = SharedConversations::query()
            ->where('conversation_id', '=', $conversation->id)
            ->first();

        if ($sharedConversation) {
            return response()->json(
                ['message' => 'You have shared this conversation already'],
                409
            );
        }

        $sharedConversation = SharedConversations::query()->create([
            'shared_url_id' => Str::random(40),
            'conversation_id' => $conversation->id,
        ]);

        Log::info('App: User with ID {user-id} shared a conversation', [
            'shared_url_id' => $sharedConversation->shared_url_id,
            'conversation_id' => $conversation->id,
        ]);

        return response()->json([
            'shared_url_id' => $sharedConversation->shared_url_id,
        ]);
    }

    public function deleteShare(Request $request)
    {
        $request->validate([
            'conversation_id' => [
                'bail',
                'required',
                'string',
                'exists:conversations,url_id',
                new ValidateConversationOwner(),
            ],
        ]);

        $conversation = Conversations::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->first();

        SharedConversations::query()
            ->where('conversation_id', '=', $conversation->id)
            ->delete();

        Log::info('App: User with ID {user-id} deleted a shared conversation', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function peek(string $id)
    {
        $conversation = Conversations::query()->where('url_id', $id)->first();

        if (!$conversation) {
            return redirect('/');
        }

        $messages = Messages::query()
            ->leftJoin('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->leftJoin('modules', 'modules.id', '=', 'conversations.module_id')
            ->leftJoin('agents', 'agents.id', '=', 'conversations.agent_id')
            ->where('messages.conversation_id', '=', $conversation->id)
            ->orderBy('messages.created_at')
            ->select([
                'messages.*',
                'conversations.id AS conversation_id',
                'conversations.name AS conversation_name',
                'modules.name AS module_name',
                'agents.name AS agent_name',
            ])
            ->get();

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $conversation->name,
            'hasPrompt' => false,
            'showOptions' => true,
            'username' =>
                User::query()->find($conversation->user_id)->name ?? null,
            'info' => null,
        ]);
    }
}
