<?php

namespace App\Http\Controllers;

use App\Classes\ChromaDB;
use App\Models\Conversation;
use App\Models\ConversationHasDocument;
use App\Models\Document;
use App\Models\Message;
use App\Models\Module;
use App\Rules\ValidateConversationOwner;
use App\Traits\AppSupportTraits;
use App\Traits\HandlesMessageLimits;
use App\Traits\OpenAICommunication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ChatController extends Controller
{
    use OpenAICommunication, AppSupportTraits, HandlesMessageLimits;

    public function show(string $id)
    {
        $conversation = Conversation::query()->where('url_id', $id)->first();

        $messages = MessageController::getMessagesForChat($id);

        if (!$messages) {
            return redirect('/');
        }

        $date = Document::query()
                ->where('collection_id', '=', $conversation->collection_id)
                ->orderBy('updated_at', 'desc')
                ->first()->updated_at ?? null;

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $conversation->name,
            'hasPrompt' => true,
            'showOptions' => true,
            'username' => null,
            'info' => session()->pull('info_message_remaining_messages'),
            'conversation_module' => Module::query()->find(
                $conversation->module_id
            )->name ?? 'Not available',
            'data_from' => $date,
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => [
                'required',
                'string',
                'min:' . config('chat.min_message_length'),
                'max:' . config('chat.max_message_length'),
            ],
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

        $appCheckResults = $this->validateChatFunctionality($conversation);

        if (!$appCheckResults) {
            return $this->returnInternalServerError();
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
            $promptWithContext = ChromaDB::createPromptWithContext(
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

            return $this->returnInternalServerError();
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

            return $this->returnInternalServerError();
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

        $responseData = $message->only(['user_message', 'agent_message', 'id']);

        $conversation->updated_at = now();
        $conversation->save();

        DB::commit();

        $remaining = $this->checkRemainingMessages();

        if ($remaining) {
            $responseData['info'] = $remaining;
        }

        return response()->json($responseData);
    }
}
