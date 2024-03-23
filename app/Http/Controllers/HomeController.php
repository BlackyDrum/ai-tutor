<?php

namespace App\Http\Controllers;

use App\Models\Agents;
use App\Models\Collections;
use App\Models\Conversations;
use App\Models\Messages;
use App\Models\Modules;
use App\Models\User;
use App\Rules\ValidateConversationOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function show()
    {
        return Inertia::render('Home');
    }

    public static function validateAppFunctionality()
    {
        if (!Auth::user()->module_id) {
            Log::warning(
                'App: User with ID {user-id} is not associated with a module'
            );

            return false;
        }

        $module = Modules::query()->find(Auth::user()->module_id);

        $agent = Agents::query()
            ->where('module_id', '=', $module->id)
            ->where('active', '=', true)
            ->first();

        if (!$agent) {
            Log::critical(
                'App: Failed to find active agent for module with ID {module-id}',
                [
                    'module-id' => $module->id,
                ]
            );

            return false;
        }

        $collection = Collections::query()
            ->where('module_id', '=', Auth::user()->module_id)
            ->first();

        if (!$collection) {
            Log::critical(
                'App: Failed to find a collection for module with ID {module-id}',
                [
                    'module-id' => Auth::user()->module_id,
                ]
            );

            return false;
        }

        return [
            'module' => $module,
            'agent' => $agent,
            'collection' => $collection,
        ];
    }

    public static function getBearerToken()
    {
        // To gracefully handle potential errors such as network issues, we encapsulate ALL Guzzle
        // HTTP requests in a try-catch block. This approach ensures better error handling by capturing
        // exceptions such as 'RequestException' or 'ConnectionException'. Laravel's HTTP client wrapper does not
        // throw exceptions on client or server errors (400 and 500 level responses from servers). Instead,
        // we have to determine if the request failed using $response->failed().
        try {
            $response = Http::withoutVerifying()
                ->asForm()
                ->post(config('conversaition.url') . '/token', [
                    'username' => config('conversaition.username'),
                    'password' => config('conversaition.password'),
                    'grant_type' => config('conversaition.grant_type'),
                    'scope' => config('conversaition.scope'),
                    'client_id' => config('conversaition.client_id'),
                    'client_secret' => config('conversaition.client_secret'),
                ]);
        } catch (\Exception $exception) {
            Log::error(
                'App/ConversAItion: Failed to get bearer token. Reason: {reason}. Status: {status}',
                [
                    'reason' => $exception->getMessage(),
                    'status' => 500,
                ]
            );

            return [
                'reason' => 'Internal Server Error',
                'status' => 500,
            ];
        }

        if ($response->failed()) {
            Log::error(
                'ConversAItion: Failed to get bearer token. Reason: {reason}. Status: {status}',
                [
                    'reason' => $response->reason(),
                    'status' => $response->status(),
                ]
            );

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
            'terms_accepted' => 'required|accepted',
        ]);

        User::query()
            ->find(Auth::id())
            ->update([
                'terms_accepted_at' => date('Y-m-d H:i:s'),
            ]);

        Log::info('App: User with ID {user-id} accepted the terms');

        return response()->json(['accepted' => true]);
    }
}
