<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatHistoryController;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('welcome');
});

// OAuth2 Protected Resource Metadata (MCP Authorization Discovery)
Route::get('/.well-known/oauth-protected-resource', function () {
    return response()->json([
        'resource' => config('app.url'),
        'authorization_servers' => [
            config('keycloak.base_url') . '/realms/' . config('keycloak.realm')
        ],
        'bearer_methods_supported' => ['header'],
        'resource_documentation' => config('app.url') . '/docs',
    ]);
});

Route::get('/chat-history/{thread_id}', [ChatHistoryController::class, 'show']);

Route::post('/chat/stream', [ChatController::class, 'stream']);