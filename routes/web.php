<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmbeddingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
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

    Route::prefix('chat')->name('chat.')->group(function() {
        Route::get('/{id}', [ChatController::class, 'show'])->name('show');

        Route::middleware(ValidateRemainingRequests::class)->group(function() {
            Route::post('/create-conversation', [HomeController::class, 'createConversation'])->name('conversation.create');

            Route::post('/chat-agent', [ChatController::class, 'chat'])->name('agent.chat');
        });
    });
});

require __DIR__.'/auth.php';
