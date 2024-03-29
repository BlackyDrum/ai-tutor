<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Collections;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        $agent = Agent::query()
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
                $agent = Agent::query()->findOrFail($conversation->agent_id);
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

        if ($conversation) {
            try {
                $collection = Collections::query()->findOrFail(
                    $conversation->collection_id
                );
            } catch (ModelNotFoundException $exception) {
                $conversation->collection_id = $collection->id;
                $conversation->save();
            }
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
                'terms_accepted_at' => now(),
            ]);

        Log::info('App: User with ID {user-id} accepted the terms');

        return response()->json(['accepted' => true]);
    }
}
