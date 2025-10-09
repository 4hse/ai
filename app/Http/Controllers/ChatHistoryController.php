<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatHistoryController extends Controller
{
    /**
     * List all chat threads for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        // Get authenticated user email (used as user_id in chat history)
        $user_id = $request->input('authenticated_email');

        // Get pagination parameters
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        // Retrieve chat histories for the user
        $chatHistories = ChatHistory::where('user_id', $user_id)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $chatHistories->items(),
            'pagination' => [
                'current_page' => $chatHistories->currentPage(),
                'total_pages' => $chatHistories->lastPage(),
                'per_page' => $chatHistories->perPage(),
                'total' => $chatHistories->total(),
            ],
        ]);
    }

    /**
     * Show a specific chat thread
     */
    public function show(Request $request, string $thread_id): JsonResponse
    {
        // Get authenticated user email (used as user_id in chat history)
        $user_id = $request->input('authenticated_email');

        // Find chat history by primary key (thread_id)
        $chatHistory = ChatHistory::find($thread_id);

        if (!$chatHistory || $chatHistory->user_id !== $user_id) {
            return response()->json([
                'error' => 'Thread not found or access denied'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $chatHistory,
        ]);
    }

    /**
     * Delete a specific chat thread
     */
    public function destroy(Request $request, string $thread_id): JsonResponse
    {
        // Get authenticated user email (used as user_id in chat history)
        $user_id = $request->input('authenticated_email');

        // Find chat history by primary key (thread_id)
        $chatHistory = ChatHistory::find($thread_id);

        if (!$chatHistory || $chatHistory->user_id !== $user_id) {
            return response()->json([
                'error' => 'Thread not found or access denied'
            ], 404);
        }

        $chatHistory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Thread deleted successfully'
        ]);
    }
}
