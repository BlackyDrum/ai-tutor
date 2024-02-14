<?php

namespace App\Http\Controllers;

use App\Models\Conversations;
use App\Models\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function show()
    {
        return Inertia::render('Home');
    }

    public function createConversation(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:1'
        ]);

        $token = self::getBearerToken();

        $message = $request->input('message');

        $response1 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/create-conversation', [
            'agent_id' => 'da9bdacf-9a0f-4e77-bc48-5e6656b87674',
            'creating_user' => config('api.username'),
            'max_tokens' => 1000,
            'temperature' => 0.5,
        ]);

        $conversationID = $response1->json()['id'];

        Conversations::query()->create([
            'id' => $conversationID,
            'agent_id' => 'da9bdacf-9a0f-4e77-bc48-5e6656b87674',
            'creating_user' => config('api.username'),
            'max_tokens' => 1000,
            'temperature' => 0.5,
            'user_id' => Auth::id(),
        ]);

        $response2 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $conversationID,
            'message' => $message
        ]);

        Messages::query()->create([
            'user_message' => $message,
            'agent_message' => $response2->json()['response'],
            'conversation_id' => $conversationID
        ]);

        return response()->json(['id' => $conversationID]);
    }

    public static function getBearerToken()
    {
        $response = Http::withoutVerifying()->asForm()->post(config('api.url') . '/token', [
            'username' => config('api.username'),
            'password' => config('api.password'),
            'grant_type' => config('api.grant_type'),
            'scope' => config('api.scope'),
            'client_id' => config('api.client_id'),
            'client_secret' => config('api.client_secret'),
        ]);

        return $response->json()['access_token'];
    }
}
