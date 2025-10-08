<?php

namespace common\ai\nodes;

use common\ai\events\ProgressEvent;
use common\ai\events\SelectedAgentEvent;
use Generator;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\StartEvent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use common\ai\agents\RouterAgent;
use common\ai\schema\SelectedAgent;
use common\ai\Prompts;

class RouterNode extends Node
{

    public function __construct(protected ChatHistoryInterface $history)
    {
    }

    /**
     */
    public function __invoke(StartEvent $event, WorkflowState $state): Generator|SelectedAgentEvent
    {
        yield new ProgressEvent("Choosing the agent...");

        $selectedAgent = RouterAgent::make()
            ->structured(
                new UserMessage(str_replace('{query}', $state->get('query'), Prompts::CHOOSE_AGENT_INSTRUCTIONS)),
                SelectedAgent::class
            );

        yield new ProgressEvent("Selected agent: $selectedAgent->agentName");

        return new SelectedAgentEvent(strtolower($selectedAgent->agentName));
    }
}
