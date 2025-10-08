<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatHistoryController;

Route::get('/', function () {
    return view('welcome');
});
 
Route::get('/chat-history/{thread_id}', [ChatHistoryController::class, 'show']);