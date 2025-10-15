<?php

namespace App\Ai\Agents;

use Exception;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use App\Ai\Providers;
use NeuronAI\SystemPrompt;
use App\Ai\Prompts;

/**
 * The Router agent is designed to route user queries to the appropriate specialized agent
 */
class RouterAgent extends Agent
{
    static string $name = "router";

    /**
     * @throws Exception
     */
    protected function provider(): AIProviderInterface
    {
        return Providers::getProvider("claude-3-7-sonnet-20250219");
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [Prompts::ROUTER_AGENT_INSTRUCTIONS],
        );
    }
}
