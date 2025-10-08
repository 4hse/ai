<?php

namespace common\ai\nodes;

use common\ai\agents\AdvisorAgent;
use common\ai\agents\AssistantAgent;
use common\ai\agents\ConsultantAgent;
use common\ai\agents\GuideAgent;
use common\ai\events\ProgressEvent;
use common\ai\events\SelectedAgentEvent;
use common\ai\events\GenerationProgressEvent;
use Exception;
use Generator;
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
        yield new ProgressEvent("Calling the agent...");

        $agentName = $event->agentName;

        $agent = match ($agentName) {
            AdvisorAgent::$name => AdvisorAgent::class,
            GuideAgent::$name => GuideAgent::class,
            ConsultantAgent::$name => ConsultantAgent::class,
            AssistantAgent::$name => AssistantAgent::class,
            default => throw new Exception("Unknown agent: $agentName"),
        };

        $answer = '';
        $stream = $agent::make()
            ->withChatHistory($this->history)
            ->stream(
                new UserMessage($state->get('query')),
            );

        foreach ($stream as $text) {
            yield new GenerationProgressEvent($text);
            $answer .= $text;
        }

        $state->set('answer', $answer);

        return new StopEvent();
    }
}
