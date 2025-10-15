<?php

namespace App\Ai;

use Exception;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\AWS\BedrockRuntime;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\HttpClientOptions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Providers
{
    /**
     * @throws Exception
     */
    public static function getProvider(string $modelId): AIProviderInterface
    {
        return match ($modelId) {
            "bedrock.claude-3-sonnet-20240229-v1:0" => self::getBedrockProvider(
                "anthropic.claude-3-sonnet-20240229-v1:0",
            ),
            "gemini-2.5-flash" => self::getGeminiProvider($modelId),
            // Claude API models
            "claude-3-7-sonnet-20250219" => self::getClaudeProvider($modelId),
            "claude-3-5-haiku-20241022" => self::getClaudeProvider($modelId),
            "claude-sonnet-4-5-20250929" => self::getClaudeProvider($modelId),
            default => throw new Exception("Model not found: $modelId"),
        };
    }

    protected static function getClaudeProvider(
        string $modelId,
    ): AIProviderInterface {

        Log::debug('claude key ' . Config::get("ai.providers.claude.key"));

        return new Anthropic(
            key: Config::get("ai.providers.claude.key"),
            model: $modelId,
            parameters: [],
            httpOptions: new HttpClientOptions(timeout: 30),
        );
    }

    protected static function getGeminiProvider(
        string $modelId,
    ): AIProviderInterface {
        return new Gemini(
            key: Config::get("ai.providers.gemini.key"),
            model: $modelId,
            parameters: [],
            httpOptions: new HttpClientOptions(timeout: 30),
        );
    }

    protected static function getBedrockProvider(
        string $modelId,
    ): AIProviderInterface {
        $client = new BedrockRuntimeClient([
            "version" => "latest",
            "region" => Config::get("ai.providers.bedrock.region"),
            "credentials" => [
                "key" => Config::get("ai.providers.bedrock.key"),
                "secret" => Config::get("ai.providers.bedrock.secret"),
            ],
        ]);

        return new BedrockRuntime(
            bedrockRuntimeClient: $client,
            model: $modelId, //retrieved via `aws bedrock list-foundation-models --region eu-west-1 --query "modelSummaries[?contains(inferenceTypesSupported, 'ON_DEMAND')].modelId"`
            inferenceConfig: [],
        );
    }
}
