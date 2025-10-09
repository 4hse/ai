<?php

namespace App\Ai\Events;

use NeuronAI\Workflow\Event;

class SelectedAgentEvent implements Event
{
    public function __construct(public string $agentName)
    {
    }
}
