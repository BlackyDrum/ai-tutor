<?php

namespace App\Http\Controllers;

use App\AppSupportTraits;
use App\HandlesMessageLimits;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Module;
use App\Models\SharedConversation;
use App\Models\User;
use App\OpenAICommunication;
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
    use OpenAICommunication, AppSupportTraits, HandlesMessageLimits;

    public function create(Request $request)
    {
        $request->validate([
            'message' =>
                'required|string|max:' . config('chat.max_message_length'),
        ]);

        $appCheckResults = $this->validateAppFunctionality();

        if (!$appCheckResults) {
            return $this->returnInternalServerError();
        }

        $agent = $appCheckResults['agent'];
        $collection = $appCheckResults['collection'];
        $module = Module::query()->find(Auth::user()->module_id);

        $count = Conversation::query()
            ->where('user_id', '=', Auth::id())
            ->count();

        // We use a transaction here in case something fails
        // to prevent us from having en empty conversation with
        // no messages
        DB::beginTransaction();

        $conversation = Conversation::query()->create([
            'name' => 'Chat #' . ($count + 1),
            'url_id' => Str::orderedUuid()->toString(),
            'agent_id' => $agent->id,
            'user_id' => Auth::id(),
            'module_id' => $module->id,
            'collection_id' => $collection->id,
        ]);

        // Get the current time and save it in the 'created_at' field when
        // we create the message at the bottom of this function.
        // This current timestamp is saved before we add records to the 'conversation_has_document'
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
                ]
            );

            DB::rollBack();

            return $this->returnInternalServerError();
        }

        $response = $this->sendMessageToOpenAI(
            systemMessage: $agent->instructions,
            userMessage: $promptWithContext,
            languageModel: $agent->openai_language_model,
            max_tokens: $agent->max_response_tokens,
            temperature: $agent->temperature
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

            return $this->returnInternalServerError();
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

        $nameCreatorModel = config(
            'api.openai_conversation_title_creator_model'
        );

        // Here we take the first agent and user message and send them
        // back to OpenAI to create a conversation title
        $response2 = $this->sendMessageToOpenAI(
            systemMessage: $systemMessage,
            userMessage: $request->input('message'),
            languageModel: $nameCreatorModel,
            max_tokens: 32,
            temperature: 0.8,
            recentMessages: $agentResponse,
            usesContext: false
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
                'openai_language_model' => $nameCreatorModel,
                'prompt_tokens' => $response2->json()['usage']['prompt_tokens'],
                'completion_tokens' => $response2->json()['usage'][
                    'completion_tokens'
                ],
            ]);
        }

        Message::query()->create([
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

        $remaining = $this->checkRemainingMessages();

        if ($remaining) {
            session()->put('info_message_remaining_messages', $remaining);
        }

        Log::info('App: User with ID {user-id} created a new conversation', [
            'conversation-id' => $conversation->id,
        ]);

        return response()->json(['id' => $conversation->url_id]);
    }

    public function delete(Request $request)
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

        Conversation::query()
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

    public function deleteAll(Request $request)
    {
        Conversation::query()->where('user_id', '=', Auth::id())->delete();

        return response()->json(['ok' => true]);
    }

    public function rename(Request $request)
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

        Conversation::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->update([
                'name' => $request->input('name'),
                'name_edited' => true,
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

    public function peek(string $id)
    {
        $conversation = Conversation::query()->where('url_id', $id)->first();

        $messages = MessageController::getMessagesForPeek($id);

        if (!$messages) {
            return redirect('/');
        }

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
