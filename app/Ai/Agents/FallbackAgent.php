<?php

namespace App\Ai\Agents;

use App\Ai\Prompts;
use Exception;
use NeuronAI\Agent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use App\Ai\Providers;

/**
 * The Fallback agent is designed to provide a default response
 */
class FallbackAgent extends Agent
{
    static string $name = 'consultant';

    /**
     * @throws Exception
     */
    protected function provider(): AIProviderInterface
    {
        return Providers::getProvider('gemini-2.5-flash');
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                Prompts::FALLBACK_AGENT_INSTRUCTIONS
            ],
        );
    }
}
