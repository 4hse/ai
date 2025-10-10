<?php

namespace App\Ai\Nodes;

use App\Ai\Agents\AdvisorAgent;
use App\Ai\Agents\AssistantAgent;
use App\Ai\Agents\ConsultantAgent;
use App\Ai\Agents\FallbackAgent;
use App\Ai\Agents\GuideAgent;
use App\Ai\Agents\RouterAgent;
use App\Ai\Events\ProgressEvent;
use App\Ai\Events\SelectedAgentEvent;
use App\Ai\Events\GenerationProgressEvent;
use Exception;
use Generator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolCallResultMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Workflow\StopEvent;
use Throwable;

class CallNode extends Node
{

    public function __construct(protected ChatHistoryInterface $history)
    {
    }

    /**
     * @throws AgentException
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(SelectedAgentEvent $event, WorkflowState $state): Generator|StopEvent
    {
        $agentName = $event->agentName;

        Log::info('CallNode invoked', [
            'agent' => $agentName,
            'query' => $state->get('query')
        ]);

        yield new ProgressEvent("Calling the agent $agentName...");

        // Get bearer token from state for AssistantAgent
        $bearerToken = $state->get('bearer');

        try {
            $agent = match ($agentName) {
                AdvisorAgent::$name => AdvisorAgent::make(),
                GuideAgent::$name => GuideAgent::make(),
                ConsultantAgent::$name => ConsultantAgent::make(),
                AssistantAgent::$name => new AssistantAgent($bearerToken),
                default => FallbackAgent::make(),
            };

            Log::debug('Agent instance created', ['agent' => $agentName]);

        } catch (Exception $e) {
            Log::error('Failed to create agent', [
                'agent' => $agentName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $answer = '';
        $toolCallCount = 0;

        try {
            $stream = $agent
                ->withChatHistory($this->history)
                ->stream(
                    new UserMessage($state->get('query')),
                );

            foreach ($stream as $chunk) {
                if ($chunk instanceof ToolCallMessage) {
                    $toolCallCount++;
                    Log::debug('Tool call made', [
                        'agent' => $agentName,
                        'tool' => $chunk->toolName ?? 'unknown',
                        'call_count' => $toolCallCount
                    ]);
                    yield new ProgressEvent($chunk);
                } else if ($chunk instanceof ToolCallResultMessage) {
                    Log::debug('Tool result received', [
                        'agent' => $agentName,
                        'result_length' => strlen($chunk)
                    ]);
                    yield new ProgressEvent($chunk);
                } else {
                    yield new GenerationProgressEvent($chunk);
                    $answer .= $chunk;
                }
            }

            Log::info('Agent response completed', [
                'agent' => $agentName,
                'answer_length' => strlen($answer),
                'tool_calls' => $toolCallCount
            ]);

        } catch (Throwable $e) {
            Log::error('Agent execution failed', [
                'agent' => $agentName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        $state->set('answer', $answer);

        return new StopEvent();
    }
}
