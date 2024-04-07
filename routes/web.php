<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SharedConversationController;
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
            Route::get('/{id}', [
                SharedConversationController::class,
                'show',
            ])->name('show');

            Route::get('/messages/{conversation_id}', [
                ChatController::class,
                'fetchMessagesForShare',
            ])->name('messages.fetch');

            Route::post('/', [
                SharedConversationController::class,
                'create',
            ])->name('create');

            Route::delete('/', [
                SharedConversationController::class,
                'delete',
            ])->name('delete');
        });

    Route::prefix('conversation')
        ->name('conversation.')
        ->group(function () {
            Route::delete('/', [
                ConversationController::class,
                'delete',
            ])->name('delete');

            Route::delete('/all', [
                ConversationController::class,
                'deleteAll',
            ])->name('delete.all');

            Route::patch('/name', [
                ConversationController::class,
                'rename',
            ])->name('rename');
        });

    Route::prefix('peek')
        ->name('peek.')
        ->middleware(EnsureIsAdmin::class)
        ->group(function () {
            Route::get('/{id}', [ConversationController::class, 'peek'])->name(
                'show'
            );

            Route::get('/messages/{conversation_id}', [
                ChatController::class,
                'fetchMessagesForPeek',
            ])->name('messages.fetch');
        });

    Route::prefix('chat')
        ->name('chat.')
        ->group(function () {
            Route::get('/{id}', [ChatController::class, 'show'])->name('show');

            Route::patch('/rating', [
                ChatController::class,
                'updateRating',
            ])->name('rate');

            Route::get('/messages/{conversation_id}', [
                ChatController::class,
                'fetchMessagesForChat',
            ])->name('messages.fetch');

            Route::middleware([
                ValidateRemainingRequests::class,
                CheckAcceptedTerms::class,
            ])->group(function () {
                Route::post('/create-conversation', [
                    ConversationController::class,
                    'create',
                ])->name('conversation.create');

                Route::post('/chat-agent', [
                    ChatController::class,
                    'chat',
                ])->name('agent.chat');
            });
        });
});

require __DIR__ . '/auth.php';
