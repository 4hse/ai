<?php

namespace App\Ai\events;

use NeuronAI\Workflow\Event;

class SelectedAgentEvent implements Event
{
    public function __construct(public string $agentName)
    {
    }
}
