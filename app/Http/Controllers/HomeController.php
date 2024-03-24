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
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public static function validateAppFunctionality($conversation = null)
    {
        $moduleId = Auth::user()->module_id;

        if (!$moduleId) {
            Log::warning(
                'App: User with ID {user-id} is not associated with a module'
            );

            return false;
        }

        $agent = Agents::query()
            ->where('module_id', '=', $moduleId)
            ->where('active', '=', true)
            ->first();

        if (!$agent) {
            Log::critical(
                'App: Failed to find active agent for module with ID {module-id}',
                [
                    'module-id' => $moduleId,
                ]
            );

            return false;
        }

        if ($conversation) {
            try {
                $agent = Agents::query()->findOrFail($conversation->agent_id);
            } catch (ModelNotFoundException $exception) {
                $conversation->agent_id = $agent->id;
                $conversation->save();
            }
        }

        $collection = Collections::query()
            ->where('module_id', '=', $moduleId)
            ->where('active', '=', true)
            ->first();

        if (!$collection) {
            Log::critical(
                'App: Failed to find active collection for module with ID {module-id}',
                [
                    'module-id' => $moduleId,
                ]
            );

            return false;
        }

        return [
            'agent' => $agent,
            'collection' => $collection,
        ];
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
