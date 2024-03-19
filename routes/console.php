<?php

use App\Models\AuthTokens;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete all expired auth tokens
Schedule::call(function () {
    $expireAfter = config('api.token_expiration');

    AuthTokens::query()
        ->where('created_at', '<', Carbon::now()->subSeconds($expireAfter))
        ->delete();
})->everyFiveMinutes();
