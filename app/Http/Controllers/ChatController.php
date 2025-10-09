<?php

namespace App\Http\Controllers;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\ChatHistory;
use App\Ai\Workflows\RouterWorkflow;
use Exception;
use Illuminate\Http\Request;
use App\Ai\events\GenerationProgressEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;


class ChatController extends Controller
{

    public function stream(Request $request): JsonResponse|StreamedResponse
    {
        // Get authenticated user email (used as user_id in chat history)
        $user_id = $request->input('authenticated_email');

        // Get or generate thread_id
        $thread_id = $request->input('thread_id') ?? \Illuminate\Support\Str::uuid()->toString();

        $messages = $request->input('messages', []);

        // Extract the last message from the messages array (DeepChat format)
        if (empty($messages)) {
            return response()->json([
                'error' => 'messages array is required and cannot be empty'
            ], 400);
        }

        // Get the last user message
        $lastMessage = end($messages);
        $message = $lastMessage['text'] ?? $lastMessage['message'] ?? null;

        if (!$message) {
            return response()->json([
                'error' => 'valid message is required'
            ], 400);
        }

        $chatHistory = ChatHistory::firstOrCreate(
            ['thread_id' => $thread_id],
            [
                'user_id' => $user_id,
                'messages' => []
            ]
        );

        // Get bearer token from request attributes (set by middleware)
        $bearerToken = $request->attributes->get('bearer_token');

        // Set the appropriate headers for SSE
        $response = new StreamedResponse(function () use ($message, $thread_id, $user_id, $chatHistory, $bearerToken) {
            try {
                if (!$chatHistory) {
                    throw new Exception("Unable to retrieve/create thread");
                }

                $workflow = new RouterWorkflow($message, $thread_id, $user_id, $bearerToken);
                $handler = $workflow->start();

                foreach ($handler->streamEvents() as $event) {
                    /*if ($event instanceof ProgressEvent) {
                        echo $event->message . "\n";
                    }*/
                    if ($event instanceof GenerationProgressEvent) {
                        $this->sendMessage($thread_id, $event->text);
                    }
                }

            } catch (Exception $e) {
                // Log the exception with full context
                Log::error('Chat streaming error', [
                    'thread_id' => $thread_id,
                    'user_id' => $user_id,
                    'message' => $message,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Send error message to client
                $this->sendMessage($thread_id, "service", "error");
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }

    protected function sendMessage($thread_id, $text, $type = "text"): void
    {
        $data = [
            $type => $text,
            "time" => date("H:i:s"),
            "thread_id" => $thread_id,
        ];

        echo "data: " . json_encode($data) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
