<?php

namespace App\Ai\Agents;

use Exception;
use NeuronAI\Exceptions\VectorStoreException;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use App\Ai\Providers;
use App\Ai\EmbeddingsProviders;

/**
 * The Guide agent is designed to provide documentation support for 4hse
 */
class GuideAgent extends RAG
{
    static string $name = "guide";

    /**
     * @throws Exception
     */
    protected function provider(): AIProviderInterface
    {
        return Providers::getProvider("gemini-2.5-flash");
    }

    /**
     * @throws Exception
     */
    protected function embeddings(): EmbeddingsProviderInterface
    {
        return EmbeddingsProviders::getProvider("gemini-embedding-001");
    }

    /**
     * @throws VectorStoreException
     */
    protected function vectorStore(): VectorStoreInterface
    {
        return new FileVectorStore(directory: storage_path("ai"), name: "docs");
    }
}
