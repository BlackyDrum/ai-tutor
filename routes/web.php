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


    Route::middleware(EnsureIsAdmin::class)->prefix('admin')->name('admin.')->group(function() {
        Route::get('/', [DashboardController::class, 'show'])->name('dashboard');

        Route::prefix('agents')->name('agents.')->group(function() {
            Route::get('/', [AgentController::class, 'show'])->name('show');
            Route::delete('/', [AgentController::class, 'delete'])->name('destroy');
            Route::patch('/active', [AgentController::class, 'setActive'])->name('active.update');
            Route::get('/create-agent', [AgentController::class, 'showCreate'])->name('create.show');
            Route::post('/create', [AgentController::class, 'create'])->name('create');
        });

        Route::prefix('embeddings')->name('embeddings.')->group(function() {
            Route::get('/', [EmbeddingController::class, 'show'])->name('show');
            Route::delete('/', [EmbeddingController::class, 'delete'])->name('destroy');
            Route::post('/create', [EmbeddingController::class, 'create'])->name('create');
            Route::get('/collections', [EmbeddingController::class, 'showCollections'])->name('collections.show');
            Route::delete('/collections', [EmbeddingController::class, 'deleteCollection'])->name('collection.destroy');
            Route::post('/collections/create', [EmbeddingController::class, 'createCollection'])->name('collection.create');
            Route::patch('/collections/active', [EmbeddingController::class, 'setCollectionActive'])->name('collection.update');
        });
    });
});

require __DIR__.'/auth.php';
