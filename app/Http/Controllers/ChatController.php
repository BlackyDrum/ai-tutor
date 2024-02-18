<?php

namespace App\Http\Controllers;

use App\Models\Conversations;
use App\Models\Messages;
use App\Rules\ValidateConversationOwner;
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

        return response()->json($message);
    }
}
