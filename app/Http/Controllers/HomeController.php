<?php

namespace App\Http\Controllers;

use App\Models\Agents;
use App\Models\Collections;
use App\Models\Conversations;
use App\Models\Messages;
use App\Models\Modules;
use App\Models\User;
use App\Rules\ValidateConversationOwner;
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
            'message' => 'required|string|max:' . config('api.max_message_length')
        ]);

        $token = self::getBearerToken();

        if (is_array($token)) {
            return response()->json($token['reason'], $token['status']);
        }

        $message = $request->input('message');

        $module = Modules::query()->find(Auth::user()->module_id);

        if (!$module) {
            return response()->json('You are not associated with a module. Try to login again.',500);
        }

        $agent = Agents::query()
            ->where('module_id', '=', $module->id)
            ->where('active', '=', true)
            ->first();

        if (!$agent) {
            return response()->json('Internal Server Error',500);
        }

        $response1 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/create-conversation', [
            'agent_id' => $agent->api_id,
            'creating_user' => config('api.username'),
            'max_tokens' => $module->max_tokens,
            'temperature' => $module->temperature,
        ]);

        if ($response1->failed()) {
            return response()->json($response1->reason(), $response1->status());
        }

        $conversationID = $response1->json()['id'];

        $conversation = Conversations::query()->create([
            'api_id' => $conversationID,
            'agent_id' => $agent->id,
            'creating_user' => config('api.username'),
            'max_tokens' => $module->max_tokens,
            'temperature' => $module->temperature,
            'user_id' => Auth::id(),
        ]);

        $collection = Collections::query()
            ->where('module_id', '=', $module->id)
            ->first();

        if (!$collection) {
            $conversation->delete();
            return response()->json(['message' => 'Internal Server Error'], 500);
        }

        try {
            $promptWithContext = ChromaController::createPromptWithContext($collection->name, $request->input('message'), $conversationID);
        } catch (\Exception $exception) {
            $conversation->delete();
            return response()->json(['message' => 'Internal Server Error'], 500);
        }

        $response2 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
            'conversation_id' => $conversationID,
            'message' => $promptWithContext
        ]);

        if ($response2->failed()) {
            $conversation->delete();
            return response()->json($response2->reason(), $response2->status());
        }

        Messages::query()->create([
            'user_message' => $message,
            'agent_message' => htmlspecialchars($response2->json()['response']),
            'conversation_id' => $conversation->id
        ]);

        return response()->json(['id' => $conversationID]);
    }

    public function deleteConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()]
        ]);

        $token = self::getBearerToken();

        if (is_array($token)) {
            return response()->json($token['reason'], $token['status']);
        }

        $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/delete-conversation', [
            'conversation' => $request->input('conversation_id'),
        ]);

        if ($response->failed()) {
            return response()->json($response->reason(), $response->status());
        }

        Conversations::query()
            ->where('api_id', '=', $request->input('conversation_id'))
            ->delete();

        return response()->json(['id' => $request->input('conversation_id')]);
    }

    public static function getBearerToken()
    {
        // We need to use a try-catch block here because we want to catch
        // 'cURL error 6: Could not resolve host'. Client- and Server errors
        // are handled by Laravel HTTP Client, e.g with $response->failed().
        try {
            $response = Http::withoutVerifying()->asForm()->post(config('api.url') . '/token', [
                'username' => config('api.username'),
                'password' => config('api.password'),
                'grant_type' => config('api.grant_type'),
                'scope' => config('api.scope'),
                'client_id' => config('api.client_id'),
                'client_secret' => config('api.client_secret'),
            ]);
        } catch (\Exception $exception) {
            return [
                'reason' => 'Internal Server Error',
                'status' => 500,
            ];
        }

        if ($response->failed()) {
            return [
                'reason' => $response->reason(),
                'status' => $response->status(),
            ];
        }

        return $response->json()['access_token'];
    }

    public function acceptTerms(Request $request)
    {
        $request->validate([
            'terms_accepted' => 'required|accepted'
        ]);

        User::query()->find(Auth::id())->update([
            'terms_accepted' => true,
        ]);

        return response()->json(['accepted' => true]);
    }
}
