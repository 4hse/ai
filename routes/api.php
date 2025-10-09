<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatHistoryController;
use App\Http\Controllers\ChatController;

// Protected API routes requiring Keycloak OAuth2 authentication
Route::middleware('keycloak.auth')->group(function () {
    // Chat history endpoints
    Route::get('/chat-history', [ChatHistoryController::class, 'index']);
    Route::get('/chat-history/index', [ChatHistoryController::class, 'index']);
    Route::get('/chat-history/{thread_id}', [ChatHistoryController::class, 'show']);

    // Chat streaming endpoint
    Route::post('/chat/stream', [ChatController::class, 'stream']);
});
