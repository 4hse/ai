<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatHistoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdvisorChatController;
use App\Http\Controllers\AuthorizedUserController;

// Public API routes (no authentication required)
// Rate limit: 5 requests per minute per IP
Route::middleware('throttle:5,1')->post('/advisor/chat/stream', [AdvisorChatController::class, 'stream']);

// Protected API routes requiring Keycloak OAuth2 authentication
Route::middleware('keycloak.auth')->group(function () {
    // Check user authorization for AI features (no authorization required to check)
    Route::get('/user/check-authorization', [AuthorizedUserController::class, 'checkAuthorization']);
});

// Protected API routes requiring both authentication and authorization
Route::middleware(['keycloak.auth', 'authorized.user'])->group(function () {
    // Chat history endpoints
    Route::get('/chat-history', [ChatHistoryController::class, 'index']);
    Route::get('/chat-history/index', [ChatHistoryController::class, 'index']);
    Route::get('/chat-history/{thread_id}', [ChatHistoryController::class, 'show']);
    Route::delete('/chat-history/{thread_id}', [ChatHistoryController::class, 'destroy']);

    // Chat streaming endpoint
    Route::post('/chat/stream', [ChatController::class, 'stream']);
});
