<?php

namespace App\Ai\Agents;

use App\Ai\Prompts;
use Exception;
use NeuronAI\Exceptions\VectorStoreException;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use App\Ai\Providers;
use App\Ai\EmbeddingsProviders;
use NeuronAI\SystemPrompt;

/**
 * The Advisor agent is designed to provide commercial support for 4hse
 */
class AdvisorAgent extends RAG
{
    static string $name = 'advisor';

    /**
     * @throws Exception
     */
    protected function provider(): AIProviderInterface
    {
        return Providers::getProvider('gemini-2.5-flash');
    }

    /**
     * @throws Exception
     */
    protected function embeddings(): EmbeddingsProviderInterface
    {
        return EmbeddingsProviders::getProvider('gemini-embedding-001');
    }

    /**
     * @throws VectorStoreException
     */
    protected function vectorStore(): VectorStoreInterface
    {
        return new FileVectorStore(
            directory: storage_path('ai'),
            name: 'www'
        );
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                Prompts::ADVISOR_AGENT_INSTRUCTIONS
            ],
        );
    }
}
