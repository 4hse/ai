<?php

namespace App\Ai\Nodes;

use App\Ai\Events\ProgressEvent;
use App\Ai\Events\SelectedAgentEvent;
use Generator;
use Illuminate\Support\Facades\Log;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use App\Ai\Agents\RouterAgent;
use App\Ai\Schema\SelectedAgent;
use App\Ai\Prompts;

class RouterNode extends Node
{

    public function __construct(protected ChatHistoryInterface $history)
    {
    }

    /**
     */
    public function __invoke(StartEvent $event, WorkflowState $state): Generator|SelectedAgentEvent
    {
        Log::debug('RouterNode started', [
            'query' => $state->get('query')
        ]);

        yield new ProgressEvent("Choosing the agent...");

        $selectedAgent = RouterAgent::make()
            ->structured(
                new UserMessage(str_replace('{query}', $state->get('query'), Prompts::CHOOSE_AGENT_INSTRUCTIONS)),
                SelectedAgent::class
            );

        Log::info('Agent selected by router', [
            'agent' => $selectedAgent->agentName,
            'reasoning' => $selectedAgent->reasoning ?? 'N/A'
        ]);

        yield new ProgressEvent("Selected agent: $selectedAgent->agentName");

        return new SelectedAgentEvent(strtolower($selectedAgent->agentName));
    }
}
