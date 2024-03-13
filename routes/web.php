<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChromaController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\CheckAcceptedTerms;
use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\ValidateRemainingRequests;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function() {
    Route::get('/', [HomeController::class, 'show'])->name('home');

    Route::patch('/accept-terms', [HomeController::class, 'acceptTerms'])->name('terms');

    Route::prefix('chat')->name('chat.')->group(function() {
        Route::get('/{id}', [ChatController::class, 'show'])->name('show');

        Route::get('/share/{id}', [ChatController::class, 'share'])->name('share');

        Route::prefix('conversation')->name('conversation.')->group(function() {
            Route::delete('/', [HomeController::class, 'deleteConversation'])->name('delete');

            Route::patch('/name', [HomeController::class, 'renameConversation'])->name('rename');

            Route::post('/share/', [ChatController::class, 'createShare'])->name('share');

            Route::delete('/share', [ChatController::class, 'deleteShare'])->name('share.delete');
        });

        Route::middleware([ValidateRemainingRequests::class, CheckAcceptedTerms::class])->group(function() {
            Route::post('/create-conversation', [HomeController::class, 'createConversation'])->name('conversation.create');

            Route::post('/chat-agent', [ChatController::class, 'chat'])->name('agent.chat');
        });
    });
});

require __DIR__.'/auth.php';
