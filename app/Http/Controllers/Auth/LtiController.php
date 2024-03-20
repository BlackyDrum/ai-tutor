<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use LonghornOpen\LaravelCelticLTI\LtiTool;

class LtiController extends Controller
{
    public function ltiMessage(Request $request)
    {
        $tool = LtiTool::getLtiTool();
        $tool->handleRequest();

        if ($tool->getLaunchType() === $tool::LAUNCH_TYPE_LAUNCH) {
            die(
                "LTI Launch successfull. Full name: {$tool->userResult->fullname}, Abbreviation: {$tool->userResult->ltiUserId}, Ref ID: {$tool->context->ltiContextId}"
            );
        }

        die('LTI Launch not failed');
    }
}
