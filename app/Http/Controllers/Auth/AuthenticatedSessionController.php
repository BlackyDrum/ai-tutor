<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Agents;
use App\Models\AuthTokens;
use App\Models\Modules;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function prepareLaunch(Request $request)
    {
        $token = config('api.auth_key');

        if (empty($token)) {
            Log::error('Auth-Prepare: Auth Key is not set in the environment file');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $name = $request->input('user');
        $refId = $request->input('ref_id');

        if ($request->bearerToken() != $token) {
            Log::warning('Auth-Prepare: Unauthorized access attempt detected. Bearer token mismatch');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$name || !$refId || !is_string($name)) {
            Log::info('Auth-Prepare: Validation failed. Missing or invalid parameters (name or refId)', [
                'name' => $name,
                'ref-id' => $refId
            ]);

            return response()->json(['message' => 'Unprocessable Content'], 422);
        }

        $module = Modules::query()
            ->where('ref_id', '=', $refId)
            ->first();

        if (!$module) {
            Log::info('Auth-Prepare: Module lookup failed. Invalid Ref ID provided', [
                'refId' => $refId
            ]);

            return response()->json(['message' => 'Invalid Ref ID'], 422);
        }

        $authToken = AuthTokens::query()->create([
            'name' => $name,
            'ref_id' => $refId,
            'token' => Str::random(40)
        ]);

        Log::info('Auth-Prepare: Created new auth token for user {name}', [
            'name' => $name,
            'ref-id' => $refId,
        ]);

        return response()->json(['user' => $authToken->name, 'ref_id' => $authToken->ref_id, 'token' => $authToken->token]);
    }

    public function launch(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();
        }

        $key = $request->input('token');
        $name = $request->input('user');

        if (!$key || !$name || !is_string($key) || !is_string($name)) {
            Log::info('Auth-Launch: Validation failed. Missing or invalid parameters (name or key).', [
                'name' => $name,
            ]);

            return response()->json(['message' => 'Unprocessable Content'], 422);
        }

        $authToken = AuthTokens::query()
            ->where('name', '=', $name)
            ->where('token', '=', $key)
            ->first();

        if (!$authToken) {
            Log::warning('Auth-Launch: Failed authentication attempt due to unmatched token or name.', [
                'name' => $name
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $expireAfter = config('api.token_expiration');

        if (Carbon::parse($authToken->created_at)->lt(now()->subSeconds($expireAfter))) {
            Log::info('Auth-Launch: Token session expired', [
                'tokenCreatedAt' => $authToken->created_at, 'expireAfterSeconds' => $expireAfter
            ]);

            return response()->json(['message' => 'Session Expired'], 419);
        }

        $module = Modules::query()
            ->where('ref_id', '=', $authToken->ref_id)
            ->first();

        if (!$module) {
            Log::info('Auth-Launch: Module lookup failed for {ref-id}', [
                'ref_id' => $authToken->ref_id
            ]);

            return response()->json(['message' => 'Unprocessable Content'], 422);
        }

        $user = User::updateOrCreate([
            'name' => $name,
        ], [
            'password' => Hash::make(Str::random(40)),
            'admin' => false,
            'module_id' => $module->id,
            'max_requests' => config('api.max_requests'),
        ]);

        $authToken->delete();

        Auth::login($user);

        $request->session()->regenerate();

        Log::info('Auth-Launch: Authentication successful. Logging in user with name {name}', [
            'name' => $name
        ]);

        return redirect('/');
    }
}
