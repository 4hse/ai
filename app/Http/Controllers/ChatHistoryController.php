<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\JsonResponse;

class ChatHistoryController extends Controller
{
    public function show(string $thread_id): JsonResponse
    {
        $chatHistory = ChatHistory::where('thread_id', $thread_id)->first();

        if (!$chatHistory) {
            return response()->json([
                'error' => 'Thread not found'
            ], 404);
        }

        return response()->json($chatHistory);
    }
}
