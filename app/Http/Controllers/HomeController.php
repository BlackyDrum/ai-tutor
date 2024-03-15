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
use Illuminate\Support\Facades\Log;
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

        Log::info('App: User with ID {user-id} is trying to create a new conversation', [
            'message' => $request->input('message')
        ]);

        $token = self::getBearerToken();

        if (is_array($token)) {
            return response()->json($token['reason'], $token['status']);
        }

        if (!Auth::user()->module_id) {
            Log::warning('App: User with ID {user-id} is not associated with a module');

            return response()->json('You are not associated with a module. Try to login again.',500);
        }

        $module = Modules::query()->find(Auth::user()->module_id);

        if (!$module) {
            Log::error('App: Failed to find module with ID {module-id}', [
                'module-id' => Auth::user()->module_id,
            ]);

            return response()->json('Internal Server Error',500);
        }

        $agent = Agents::query()
            ->where('module_id', '=', $module->id)
            ->where('active', '=', true)
            ->first();

        if (!$agent) {
            Log::critical('App: Failed to find active agent for module with ID {module-id}', [
                'module-id' => $module->id,
            ]);

            return response()->json('Internal Server Error',500);
        }

        try {
            $response1 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/create-conversation', [
                'agent_id' => $agent->api_id,
                'creating_user' => config('api.username'),
                'max_tokens' => $module->max_tokens,
                'temperature' => $module->temperature,
            ]);
        } catch (\Exception $exception) {
            Log::error('App/ConversAItion: Failed to create conversation. Reason: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json('Internal Server Error', '500');
        }

        if ($response1->failed()) {
            Log::error('ConversAItion: Failed to create conversation. Reason: {reason}. Status: {status}', [
                'reason' => $response1->reason(),
                'status' => $response1->status(),
                'agent-id' => $agent->id,
                'agent-api-id' => $agent->api_id,
                'max-tokens' => $module->max_tokens,
                'temperature' => $module->temperature,
            ]);

            return response()->json($response1->reason(), $response1->status());
        }

        $conversationID = $response1->json()['id'];

        $count = Conversations::query()
            ->where('user_id', '=', Auth::id())
            ->count();

        $conversation = Conversations::query()->create([
            'name' => 'Chat #' . ($count + 1),
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
            Log::critical('App: Failed to find a collection for module with ID {module-id}', [
                'module-id' => $module->id
            ]);

            $conversation->delete();
            return response()->json(['message' => 'Internal Server Error'], 500);
        }

        try {
            $promptWithContext = ChromaController::createPromptWithContext($collection->name, $request->input('message'), $conversationID);
        } catch (\Exception $exception) {
            Log::error('ChromaDB: Failed to create prompt with context. Reason: {message}', [
                'message' => $exception->getMessage(),
                'collection' => $collection->name,
                'conversation-id' => $conversationID
            ]);

            $conversation->delete();
            return response()->json(['message' => 'Internal Server Error'], 500);
        }

        try {
            $response2 = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/chat-agent', [
                'conversation_id' => $conversationID,
                'message' => $promptWithContext
            ]);
        } catch (\Exception $exception) {
            Log::error('App/ConversAItion: Failed to send message. Reason: {message}', [
                'message' => $exception->getMessage(),
                'conversation-id' => $conversationID,
            ]);

            $conversation->delete();
            return response()->json('Internal Server Error', '500');
        }

        if ($response2->failed()) {
            Log::error('ConversAItion: Failed to send message. Reason: {reason}. Status: {status}', [
                'reason' => $response2->reason(),
                'status' => $response2->status(),
                'conversation-id' => $conversationID,
            ]);

            $conversation->delete();
            return response()->json($response2->reason(), $response2->status());
        }

        Messages::query()->create([
            'user_message' => $request->input('message'),
            'agent_message' => htmlspecialchars($response2->json()['response']),
            'prompt_tokens' => $response2->json()['prompt_tokens'],
            'completion_tokens' => $response2->json()['completion_tokens'],
            'conversation_id' => $conversation->id
        ]);

        Log::info('App: User with ID {user-id} created a new conversation', [
            'conversation-id' => $conversationID
        ]);

        return response()->json(['id' => $conversationID]);
    }

    public function deleteConversation(Request $request)
    {
        $request->validate([
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()]
        ]);

        Log::info('App: User with ID {user-id} is trying to delete a conversation with ID {conversation-id}', [
            'conversation-id' => $request->input('conversation_id')
        ]);

        $token = self::getBearerToken();

        if (is_array($token)) {
            return response()->json($token['reason'], $token['status']);
        }

        try {
            $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/delete-conversation', [
                'conversation' => $request->input('conversation_id'),
            ]);
        } catch (\Exception $exception) {
            Log::error('App/ConversAItion: Failed to delete conversation. Reason: {message}', [
                'message' => $exception->getMessage(),
                'conversation-id' => $request->input('conversation_id'),
            ]);

            return response()->json('Internal Server Error', '500');
        }

        if ($response->failed()) {
            Log::error('ConversAItion: Failed to delete conversation. Reason: {reason}. Status: {status}', [
                'reason' => $response->reason(),
                'status' => $response->status(),
                'conversation-id' => $request->input('conversation_id'),
            ]);

            return response()->json($response->reason(), $response->status());
        }

        Conversations::query()
            ->where('api_id', '=', $request->input('conversation_id'))
            ->delete();

        Log::info('App: User with ID {user-id} deleted a conversation with ID {conversation-id}', [
            'conversation-id' => $request->input('conversation_id')
        ]);

        return response()->json(['id' => $request->input('conversation_id')]);
    }

    public function renameConversation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'conversation_id' => ['bail', 'required', 'string', 'exists:conversations,api_id', new ValidateConversationOwner()]
        ]);

        Conversations::query()
            ->where('api_id', '=', $request->input('conversation_id'))
            ->update([
                'name' => $request->input('name')
            ]);

        Log::info('User with ID {user-id} renamed a conversation with ID {conversation-id}', [
            'new-name' => $request->input('name'),
            'conversation-id' => $request->input('conversation_id')
        ]);

        return response()->json(['name' => $request->input('name'), 'id' => $request->input('conversation_id')]);
    }

    public static function getBearerToken()
    {
        // To gracefully handle potential errors such as network issues, we encapsulate ALL Guzzle
        // HTTP requests in a try-catch block. This approach ensures better error handling by capturing
        // exceptions such as 'RequestException' or 'ConnectionException'. Laravel's HTTP client wrapper does not
        // throw exceptions on client or server errors (400 and 500 level responses from servers). Instead,
        // we have to determine if the request failed using $response->failed().
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
            Log::error('App/ConversAItion: Failed to get bearer token. Reason: {reason}. Status: {status}', [
                'reason' => $exception->getMessage(),
                'status' => 500,
            ]);

            return [
                'reason' => 'Internal Server Error',
                'status' => 500,
            ];
        }

        if ($response->failed()) {
            Log::error('ConversAItion: Failed to get bearer token. Reason: {reason}. Status: {status}', [
                'reason' => $response->reason(),
                'status' => $response->status(),
            ]);

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
            'terms_accepted_at' => date('Y-m-d H:i:s'),
        ]);

        Log::info('App: User with ID {user-id} accepted the terms');

        return response()->json(['accepted' => true]);
    }
}
