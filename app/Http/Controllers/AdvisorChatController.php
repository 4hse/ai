<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AdvisorAgent;
use App\Ai\Events\GenerationProgressEvent;
use App\Ai\History\LaravelChatHistory;
use App\Models\ChatHistory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolCallResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvisorChatController extends Controller
{
    private const DAILY_LIMIT = 20;
    private const RATE_LIMIT_MINUTES = 5;

    public function stream(Request $request): JsonResponse|StreamedResponse
    {
        // Get client IP address as user identifier
        $userIp = $request->ip();

        // Check daily message limit
        $dailyKey = 'advisor_daily_limit:' . $userIp;
        $dailyCount = Cache::get($dailyKey, 0);

        // Get or generate thread_id
        $thread_id = $request->input('thread_id') ?? \Illuminate\Support\Str::uuid()->toString();

        if ($dailyCount >= self::DAILY_LIMIT) {
            return new StreamedResponse(function () use ($thread_id) {
               $this->sendMessage('Daily limit reached', 'error', $thread_id) ;
            });
        }

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

        // Check if thread exists and verify ownership
        $chatHistory = ChatHistory::find($thread_id);

        if ($chatHistory) {
            // Thread exists, verify it belongs to the same IP
            if ($chatHistory->user_id !== $userIp) {
                return response()->json([
                    'error' => 'Access denied to this thread'
                ], 403);
            }
        } else {
            // Create new thread for this IP
            $chatHistory = ChatHistory::create([
                'thread_id' => $thread_id,
                'user_id' => $userIp,
                'messages' => []
            ]);
        }

        // Set the appropriate headers for SSE
        $response = new StreamedResponse(function () use ($message, $thread_id, $userIp) {
            try {
                // Create advisor agent instance
                $agent = AdvisorAgent::make();

                // Use Laravel chat history to persist conversations
                $history = new LaravelChatHistory(
                    thread_id: $thread_id,
                    user_id: $userIp,
                    contextWindow: 50000
                );

                // Stream the response
                $stream = $agent
                    ->withChatHistory($history)
                    ->stream(new UserMessage($message));

                foreach ($stream as $chunk) {
                    if ($chunk instanceof ToolCallMessage) {
                        // Tool call in progress
                        $this->sendMessage('Searching for information...', 'progress', $thread_id);
                    } elseif ($chunk instanceof ToolCallResultMessage) {
                        // Tool result received
                        $this->sendMessage('Processing results...', 'progress', $thread_id);
                    } else {
                        // Text chunk
                        $this->sendMessage($chunk, 'text', $thread_id);
                    }
                }

                // Increment daily message counter after successful completion
                $dailyKey = 'advisor_daily_limit:' . $userIp;
                $currentCount = Cache::get($dailyKey, 0);
                Cache::put($dailyKey, $currentCount + 1, now()->endOfDay());

            } catch (Exception $e) {
                // Log the exception with full context
                Log::error('Advisor chat streaming error', [
                    'thread_id' => $thread_id,
                    'user_ip' => $userIp,
                    'message' => $message,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Send error message to client
                $this->sendMessage('An error occurred while processing your request', 'error', $thread_id);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        // Add rate limit information headers
        $dailyKey = 'advisor_daily_limit:' . $userIp;
        $currentDailyCount = Cache::get($dailyKey, 0);
        $response->headers->set('X-RateLimit-Limit-Daily', self::DAILY_LIMIT);
        $response->headers->set('X-RateLimit-Remaining-Daily', max(0, self::DAILY_LIMIT - $currentDailyCount));
        $response->headers->set('X-RateLimit-Reset', now()->endOfDay()->timestamp);

        return $response;
    }

    protected function sendMessage(string $text, string $type = 'text', ?string $thread_id = null): void
    {
        $data = [
            $type => $text,
            'time' => date('H:i:s'),
        ];

        if ($thread_id) {
            $data['thread_id'] = $thread_id;
        }

        echo 'data: ' . json_encode($data) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}
