<?php

namespace common\ai\events;

use NeuronAI\Workflow\Event;

class SelectedAgentEvent implements Event
{
    public function __construct(public string $agentName)
    {
    }
}