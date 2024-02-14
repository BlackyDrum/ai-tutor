<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function show(string $id)
    {
        $token = HomeController::getBearerToken();

        $response = Http::withToken($token)->withoutVerifying()->get(config('api.url') . '/agents/get-messages', [
            'conversation_id' => $id
        ]);

        return Inertia::render('Chat', [
            'messages' => $response->json(),
        ]);
    }

    public function chat(Request $request)
    {
        $token = HomeController::getBearerToken();

        $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $request->input('conversation_id'),
            'message' => $request->input('message'),
        ]);
    }
}
