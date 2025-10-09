<?php

namespace App\Ai\Agents;


use NeuronAI\MCP\McpConnector;
use App\Ai\Prompts;
use Exception;
use NeuronAI\Agent;
use NeuronAI\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use App\Ai\Providers;

/**
 * The Assistant agent is designed to execute 4hse tools
 */
class AssistantAgent extends Agent
{
    static string $name = 'assistant';

    protected function tools(): array
    {
        return [
            ...McpConnector::make([
                'url' => 'http://ai:8080/mpc', //getenv('MCP_SERVER_URL') . '/mcp',
                'token' => 'BEARER_TOKEN',
                'timeout' => 30,
                'headers' => [
                    //'x-custom-header' => 'value'
                ]
            ])->tools(),
        ];
    }

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
                Prompts::ASSISTANT_AGENT_INSTRUCTIONS
            ],
        );
    }
}
