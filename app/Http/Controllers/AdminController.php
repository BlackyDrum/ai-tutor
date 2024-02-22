<?php

namespace App\Http\Controllers;

use App\Models\Agents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function show()
    {
        return Inertia::render('AdminDashboard');
    }

    public function showAgents()
    {
        $agents = Agents::query()
            ->join('users', 'users.id', '=', 'agents.user_id')
            ->select([
                'agents.*',
                'users.name AS creator'
            ])
            ->get();

        return Inertia::render('Agents', [
            'agents' => $agents
        ]);
    }

    public function showCreateAgent()
    {
        return Inertia::render('CreateAgent');
    }

    public function deleteAgent(Request $request)
    {
        $request->validate([
            'id' => 'required|string|exists:agents,id'
        ]);

        $agent = Agents::query()->find($request->input('id'));

        if ($agent->active) {
            return response()->json(['message' => 'You cannot delete an active agent'], 422);
        }

        $agent->delete();

        return response()->json(['id' => $request->input('id')]);

    }

    public function createAgent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:agents,name',
            'context' => 'required|string',
            'first_message' => 'required|string',
            'response_shape' => 'required|string',
            'instructions' => 'required|string'
        ]);

        $token = HomeController::getBearerToken();

        $response = Http::withToken($token)->withoutVerifying()->post(config('api.url') . '/agents/create-agent', [
            'name' => $request->input('name'),
            'context' => $request->input('context'),
            'first_message' => $request->input('first_message'),
            'response_shape' => $request->input('response_shape'),
            'instructions' => $request->input('instructions'),
            'creating_user' => config('api.username')
        ]);

        if ($response->failed()) {
            return back()->withErrors(['message' => $response->reason()]);
        }

        Agents::query()->create([
            'id' => $response->json()['id'],
            'name' => $request->input('name'),
            'context' => $request->input('context'),
            'first_message' => $request->input('first_message'),
            'response_shape' => $request->input('response_shape'),
            'instructions' => $request->input('instructions'),
            'creating_user' => config('api.username'),
            'user_id' => Auth::id(),
        ]);

        return back();
    }
}
