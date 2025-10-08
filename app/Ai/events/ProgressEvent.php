<?php

namespace common\ai\events;

use NeuronAI\Workflow\Event;

class ProgressEvent implements Event
{
    public function __construct(public string $message)
    {
    }
}