<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SharedConversation;
use App\Rules\ValidateConversationOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SharedConversationController extends Controller
{
    public function show(string $id)
    {
        $messages = ChatController::getMessagesForShare($id);

        if (!$messages) {
            return redirect('/');
        }

        $shared = SharedConversation::query()
            ->where('shared_url_id', $id)
            ->first();

        $conversation = Conversation::query()->find($shared->conversation_id);

        return Inertia::render('Chat', [
            'messages' => $messages,
            'conversation_id' => $id,
            'conversation_name' => $conversation->name,
            'hasPrompt' => false,
            'showOptions' => false,
            'username' => null,
            'info' => null,
        ]);
    }

    public function create(Request $request)
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

        $conversation = Conversation::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->first();

        $sharedConversation = SharedConversation::query()
            ->where('conversation_id', '=', $conversation->id)
            ->first();

        if ($sharedConversation) {
            return response()->json(
                ['message' => 'You have shared this conversation already'],
                409
            );
        }

        $sharedConversation = SharedConversation::query()->create([
            'shared_url_id' => Str::orderedUuid()->toString(),
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

        $conversation = Conversation::query()
            ->where('url_id', '=', $request->input('conversation_id'))
            ->first();

        SharedConversation::query()
            ->where('conversation_id', '=', $conversation->id)
            ->delete();

        Log::info('App: User with ID {user-id} deleted a shared conversation', [
            'conversation_id' => $conversation->id,
        ]);
    }
}
