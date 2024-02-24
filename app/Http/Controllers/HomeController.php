<?php

namespace App\Http\Controllers;

use App\Models\Agents;
use App\Models\Conversations;
use App\Models\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'message' => 'required|string|min:1|max:' . config('api.max_message_length'),
            'collection' => 'required|integer|exists:collections,id'
        ]);

        $token = self::getBearerToken();

        if (is_array($token)) {
            return response()->json($token['reason'], intval($token['status']));
        }

        $message = $request->input('message');

        $agent = Agents::query()->where('active', '=', true)->first();

        if (empty($agent)) {
            return response()->json('Internal Server Error',500);
        }

        $response1 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/create-conversation', [
            'agent_id' => $agent->id,
            'creating_user' => config('api.username'),
            'max_tokens' => config('api.max_tokens'),
            'temperature' => config('api.temperature'),
        ]);

        if ($response1->failed()) {
            return response()->json($response1->reason(), $response1->status());
        }

        $conversationID = $response1->json()['id'];

        // We use a transaction here, allowing rollback in case the following API request fails.
        DB::beginTransaction();

        Conversations::query()->create([
            'id' => $conversationID,
            'agent_id' => $agent->id,
            'creating_user' => config('api.username'),
            'max_tokens' => config('api.max_tokens'),
            'temperature' => config('api.temperature'),
            'user_id' => Auth::id(),
        ]);

        $response2 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $conversationID,
            'message' => $message
        ]);

        if ($response2->failed()) {
            DB::rollBack();

            return response()->json($response2->reason(), $response2->status());
        }

        Messages::query()->create([
            'user_message' => $message,
            'agent_message' => htmlspecialchars($response2->json()['response']),
            'conversation_id' => $conversationID
        ]);

        DB::commit();

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

        if ($response->failed()) {
            return [
                'reason' => $response->reason(),
                'status' => $response->status(),
            ];
        }

        return $response->json()['access_token'];
    }
}
