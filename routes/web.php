<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
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

    Route::post('/create-conversation', [HomeController::class, 'createConversation'])->name('create-conversation');

    Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat');

    Route::post('/chat/chat-agent', [ChatController::class, 'chat'])->name('chat-agent');
});

require __DIR__.'/auth.php';
