<?php

namespace App\Ai\Schema;

use NeuronAI\StructuredOutput\SchemaProperty;

class SelectedAgent
{
    #[SchemaProperty(description: 'The agent name.', required: true)]
    public string $agentName;
}
