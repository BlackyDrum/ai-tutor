<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChromaController;
use App\Http\Controllers\ConversationController;
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

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'show'])->name('home');

    Route::patch('/accept-terms', [HomeController::class, 'acceptTerms'])->name(
        'terms'
    );

    Route::prefix('share')
        ->name('share.')
        ->group(function () {
            Route::get('/{id}', [ConversationController::class, 'share'])->name(
                'show'
            );

            Route::post('/', [
                ConversationController::class,
                'createShare',
            ])->name('create');

            Route::delete('/', [
                ConversationController::class,
                'deleteShare',
            ])->name('delete');
        });

    Route::prefix('conversation')
        ->name('conversation.')
        ->group(function () {
            Route::delete('/', [
                ConversationController::class,
                'deleteConversation',
            ])->name('delete');

            Route::patch('/name', [
                ConversationController::class,
                'renameConversation',
            ])->name('rename');
        });

    Route::get('/peek/{id}', [ConversationController::class, 'peek'])
        ->middleware(EnsureIsAdmin::class)
        ->name('peek');

    Route::prefix('chat')
        ->name('chat.')
        ->group(function () {
            Route::get('/{id}', [ChatController::class, 'show'])->name('show');

            Route::middleware([
                ValidateRemainingRequests::class,
                CheckAcceptedTerms::class,
            ])->group(function () {
                Route::post('/create-conversation', [
                    ConversationController::class,
                    'createConversation',
                ])->name('conversation.create');

                Route::post('/chat-agent', [
                    ChatController::class,
                    'chat',
                ])->name('agent.chat');
            });
        });
});

require __DIR__ . '/auth.php';
