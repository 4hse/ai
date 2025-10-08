<?php

namespace App\Ai\schema;

use NeuronAI\StructuredOutput\SchemaProperty;

class SelectedAgent
{
    #[SchemaProperty(description: 'The agent name.', required: true)]
    public string $agentName;
}
