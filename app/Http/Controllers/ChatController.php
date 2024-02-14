<?php

namespace App\Http\Controllers;

use App\Models\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function show(string $id)
    {
        $messages = Messages::query()->where('conversation_id', '=', $id)->orderBy('created_at')->get();

        return Inertia::render('Chat', [
            'messages' => $messages,
        ]);
    }

    public function chat(Request $request)
    {
        $token = HomeController::getBearerToken();

        $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $request->input('conversation_id'),
            'message' => $request->input('message'),
        ]);

        Messages::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => $response->json()['response'],
            'conversation_id' => $request->input('conversation_id'),
        ]);
    }
}
