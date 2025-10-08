<?php

namespace common\ai\agents;

use Exception;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use common\ai\Providers;
use NeuronAI\SystemPrompt;
use common\ai\Prompts;

/**
 * The Router agent is designed to route user queries to the appropriate specialized agent
 */
class RouterAgent extends Agent
{
    static string $name = 'router';

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
                Prompts::ROUTER_AGENT_INSTRUCTIONS
            ],
        );
    }
}
