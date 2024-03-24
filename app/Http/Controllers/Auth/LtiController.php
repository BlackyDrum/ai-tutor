<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Modules;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LonghornOpen\LaravelCelticLTI\LtiTool;

class LtiController extends Controller
{
    public function ltiMessage(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();
        }

        try {
            $tool = LtiTool::getLtiTool();
            $tool->handleRequest();
        } catch (\Exception $exception) {
            Log::info('Auth: LTI launch failed. Reason: {reason}', [
                'reason' => $exception->getMessage(),
            ]);

            return $this->redirectWithError();
        }

        if ($tool->getLaunchType() === $tool::LAUNCH_TYPE_LAUNCH) {
            $name = $tool->userResult->fullname;
            $abbreviation = $tool->userResult->ltiUserId;
            $refId = $tool->resourceLink->getId();

            $module = Modules::query()->where('ref_id', '=', $refId)->first();

            if (!$module) {
                Log::info(
                    'Auth: Module lookup failed. Invalid Ref ID provided',
                    [
                        'refId' => $refId,
                    ]
                );

                return $this->redirectWithError();
            }

            $user = User::firstOrCreate(
                [
                    'abbreviation' => $abbreviation,
                ],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(40)),
                    'admin' => false,
                    'module_id' => $module->id,
                    'max_requests' => config('chat.max_requests'),
                    'temperature' => config('chat.temperature'),
                    'max_response_tokens' => config('chat.max_response_tokens'),
                ]
            );

            $user->module_id = $module->id;
            $user->save();

            Auth::login($user);

            $request->session()->regenerate();

            Log::info(
                'Auth: Authentication successful. Logging in user with name {name}',
                [
                    'name' => $user->name,
                    'abbreviation' => $user->abbreviation,
                ]
            );

            return redirect('/');
        }

        Log::info('Auth: LTI launch failed');

        return $this->redirectWithError();
    }

    private function redirectWithError()
    {
        return redirect('/login')->withErrors(
            [
                'message' =>
                    'Authentication failed. Please try again or contact us.',
            ],
            'lti'
        );
    }
}
