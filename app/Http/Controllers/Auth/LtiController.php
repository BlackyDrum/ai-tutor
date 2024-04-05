<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
            return $this->redirectWithError($exception->getMessage());
        }

        if ($tool->getLaunchType() === $tool::LAUNCH_TYPE_LAUNCH) {
            $name = $tool->userResult->fullname;
            $abbreviation = $tool->userResult->ltiUserId;
            $role = $tool->getRawParameters()['roles'] ?? null;
            $refId = $tool->resourceLink->getId();
            $contextTitle = $tool->context->title;

            $validator = Validator::make(
                [
                    'name' => $name,
                    'abbreviation' => $abbreviation,
                    'role' => $role,
                    'refId' => $refId,
                    'context_title' => $contextTitle,
                ],
                [
                    'name' => 'required|string|max:64',
                    'abbreviation' => 'required|string|max:64',
                    'role' => 'required|string',
                    'refId' => 'required|integer|exists:modules,ref_id',
                    'context_title' => 'required|string'
                ]
            );

            if ($validator->fails()) {
                return $this->redirectWithError($validator->errors()->toJson());
            }

            $module = Module::query()->where('ref_id', '=', $refId)->first();

            $user = User::firstOrCreate(
                [
                    'abbreviation' => $abbreviation,
                ],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(40)),
                    'admin' => $role == 'Instructor',
                    'module_id' => $module->id,
                    'context_title' => $contextTitle,
                    'max_requests' => config('chat.max_requests'),
                ]
            );

            $user->module_id = $module->id;
            $user->context_title = $contextTitle;
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

        return $this->redirectWithError();
    }

    private function redirectWithError($reason = '')
    {
        Log::info('Auth: LTI launch failed. Reason: {reason}', [
            'reason' => $reason,
        ]);

        return redirect('/login')->withErrors(
            [
                'message' =>
                    'Authentication failed. Please try again or contact us.',
            ],
            'lti'
        );
    }
}
