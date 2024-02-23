<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\Agents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AgentController extends Controller
{

    public function show()
    {
        $agents = Agents::query()
            ->leftJoin('users', 'users.id', '=', 'agents.user_id')
            ->select([
                'agents.*',
                'users.name AS creator'
            ])
            ->orderBy('agents.created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Agents', [
            'agents' => $agents
        ]);
    }

    public function showCreate()
    {
        return Inertia::render('Admin/CreateAgent');
    }

    public function delete(Request $request)
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

    public function setActive(Request $request)
    {
        $request->validate([
            'id' => 'required|string|exists:agents,id'
        ]);

        DB::beginTransaction();

        Agents::query()
            ->where('active', '=', true)
            ->update(['active' => false]);

        Agents::query()
            ->find($request->input('id'))
            ->update(['active' => true]);

        DB::commit();

        return response()->json(['id' => $request->input('id')]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:agents,name',
            'context' => 'required|string|max:255',
            'first_message' => 'required|string|max:255',
            'response_shape' => 'required|string|max:255',
            'instructions' => 'required|string'
        ]);

        $token = HomeController::getBearerToken();

        if (is_array($token)) {
            return back()->withErrors(['message' => $token['reason']]);
        }

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
            'active' => Agents::query()->count() == 0
        ]);

        return back();
    }
}
