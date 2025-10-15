<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AdvisorAgent;
use App\Ai\Events\GenerationProgressEvent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolCallResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvisorChatController extends Controller
{
    public function stream(Request $request): JsonResponse|StreamedResponse
    {
        // Validate message input
        $messages = $request->input('messages', []);

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

        // Set the appropriate headers for SSE
        $response = new StreamedResponse(function () use ($message) {
            try {
                // Create advisor agent instance
                $agent = AdvisorAgent::make();

                // Use in-memory chat history for advisor conversations
                $history = new InMemoryChatHistory();

                // Stream the response
                $stream = $agent
                    ->withChatHistory($history)
                    ->stream(new UserMessage($message));

                foreach ($stream as $chunk) {
                    if ($chunk instanceof ToolCallMessage) {
                        // Tool call in progress
                        $this->sendMessage('Searching for information...', 'progress');
                    } elseif ($chunk instanceof ToolCallResultMessage) {
                        // Tool result received
                        $this->sendMessage('Processing results...', 'progress');
                    } else {
                        // Text chunk
                        $this->sendMessage($chunk, 'text');
                    }
                }

            } catch (Exception $e) {
                // Log the exception with full context
                Log::error('Advisor chat streaming error', [
                    'message' => $message,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Send error message to client
                $this->sendMessage('An error occurred while processing your request', 'error');
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    protected function sendMessage(string $text, string $type = 'text'): void
    {
        $data = [
            $type => $text,
            'time' => date('H:i:s'),
        ];

        echo 'data: ' . json_encode($data) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
