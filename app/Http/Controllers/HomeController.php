<?php

namespace App\Http\Controllers;

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
            'creating_user' => Auth::user()->name,
            'max_tokens' => 1000,
            'temperature' => 0.5,
        ]);

        $conversationID = $response1->json()['id'];

        Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $conversationID,
            'message' => $message
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
