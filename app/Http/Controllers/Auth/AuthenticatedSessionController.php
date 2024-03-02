<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuthTokens;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $name = $request->input('user');
        $refId = $request->input('ref_id');

        if ($request->bearerToken() != $token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$name || !$refId || !is_numeric($refId) || !is_string($name)) {
            return response()->json(['message' => 'Unprocessable Content'], 422);
        }

        $authToken = AuthTokens::query()->create([
            'name' => $request->input('user'),
            'ref_id' => $request->input('ref_id'),
            'token' => Str::random(40)
        ]);

        return response()->json(['user' => $authToken->name, 'ref_id' => $authToken->ref_id, 'token' => $authToken->token]);
    }

    public function launch(Request $request)
    {
        $key = $request->input('token');
        $name = $request->input('user');

        if (!$key || !$name || !is_string($key) || !is_string($name)) {
            return response()->json(['message' => 'Unprocessable Content'], 422);
        }

        $authToken = AuthTokens::query()
            ->where('name', '=', $name)
            ->where('token', '=', $key)
            ->first();

        if (!$authToken) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $expireAfter = config('api.token_expiration');

        if (Carbon::parse($authToken->created_at)->lt(now()->subSeconds($expireAfter))) {
            return response()->json(['message' => 'Session Expired'], 419);
        }

        $user = User::firstOrCreate([
            'name' => $name,
        ], [
            'password' => Hash::make(Str::random(40)),
            'admin' => false,
            'max_requests' => config('api.max_requests'),
        ]);

        $authToken->delete();

        Auth::login($user);

        return redirect('/');
    }
}
