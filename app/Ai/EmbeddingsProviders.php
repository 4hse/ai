<?php

namespace common\ai;

use Exception;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\GeminiEmbeddingsProvider;
use Illuminate\Support\Facades\Config;
class EmbeddingsProviders
{

    /**
     * @throws Exception
     */
    public static function getProvider(string $modelId): EmbeddingsProviderInterface
    {
        return match ($modelId) {
            'gemini-embedding-001' => self::getGeminiProvider($modelId),
            default => throw new Exception("Model not found: $modelId"),
        };
    }

    protected static function getGeminiProvider(string $modelId): EmbeddingsProviderInterface
    {
        return new GeminiEmbeddingsProvider(
            key: Config::get('ai.providers.gemini.key'),
            model: $modelId
        );
    }

}
