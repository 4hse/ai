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
    static string $name = "assistant";

    public function __construct(private readonly ?string $bearerToken = null) {}

    /**
     * @throws Exception
     */
    protected function tools(): array
    {
        $headers = [];
        if ($this->bearerToken) {
            $headers["Authorization"] = "Bearer " . $this->bearerToken;
        }

        return [
            ...McpConnector::make([
                "url" => getenv("MCP_SERVER_URL"),
                "timeout" => 30,
                "headers" => $headers,
            ])->tools(),
        ];
    }

    /**
     * @throws Exception
     */
    protected function provider(): AIProviderInterface
    {
        return Providers::getProvider("bedrock.claude-3-sonnet-20240229-v1:0");
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [Prompts::ASSISTANT_AGENT_INSTRUCTIONS],
        );
    }
}
