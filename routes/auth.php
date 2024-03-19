<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('auth.')
    ->group(function () {
        Route::get('/prepare-launch', [
            AuthenticatedSessionController::class,
            'prepareLaunch',
        ])->name('prepare');

        Route::get('/launch', [
            AuthenticatedSessionController::class,
            'launch',
        ])->name('launch');
    });

Route::middleware('guest')->group(function () {
    Route::get('login', [
        AuthenticatedSessionController::class,
        'create',
    ])->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ])->name('logout');
});
