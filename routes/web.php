<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatHistoryController;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});
 
Route::get('/chat-history/{thread_id}', [ChatHistoryController::class, 'show']);

Route::post('/chat/stream', [ChatController::class, 'stream']);