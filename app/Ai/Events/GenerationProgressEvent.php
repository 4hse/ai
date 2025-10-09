<?php

namespace App\Ai\Events;

use NeuronAI\Workflow\Event;

class GenerationProgressEvent implements Event
{
    public function __construct(public string $text)
    {
    }
}
