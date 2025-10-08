<?php

namespace App\Ai\agents;

use App\Ai\Prompts;
use Exception;
use NeuronAI\Agent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use App\Ai\Providers;

/**
 * The Consultant agent is designed to provide expert advice on workplace safety
 */
class ConsultantAgent extends Agent
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
                Prompts::CONSULTANT_AGENT_INSTRUCTIONS
            ],
        );
    }
}
