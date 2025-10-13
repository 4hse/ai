<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Agents\AdvisorAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\RAG;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for searching 4HSE website
 */
class WebsiteSearchTool
{
    private RAG $rag;

    public function __construct()
    {
        // Initialize the Guide Agent
        $this->rag = AdvisorAgent::make();
    }

    /**
     * Search in the 4HSE website using a natural language query.
     * Uses Neuron AI for semantic vector search.
     *
     * @param string $query The question or text to search in the website
     * @param int $limit Maximum number of results to return (default: 5)
     * @return array Search results with content and similarity score
     */
    #[
        McpTool(
            name: "search_4hse_website",
            description: "Searches the 4HSE website using natural language queries with semantic vector search",
        ),
    ]
    public function searchWebsite(
        #[
            Schema(
                type: "string",
                description: "The natural language query or question about 4HSE commercial things",
            ),
        ]
        string $query,

        #[
            Schema(
                type: "integer",
                description: "Maximum number of results to return",
                minimum: 1,
                maximum: 20,
            ),
        ]
        int $limit = 5,
    ): array {
        try {
            // Create the message for Neuron AI
            $message = new UserMessage($query);

            // Use ONLY retrieval (doesn't call the LLM!)
            $documents = $this->rag->retrieveDocuments($message);

            // Limit the results
            $documents = array_slice($documents, 0, $limit);

            // Convert Document objects to array for MCP
            $results = array_map(function ($doc) {
                return [
                    "id" => $doc->id,
                    "source_type" => $doc->sourceType,
                    "source_name" => $doc->sourceName,
                    "content" => $doc->content,
                    "similarity_score" => $doc->score ?? null,
                ];
            }, $documents);

            return [
                "query" => $query,
                "results_count" => count($results),
                "results" => $results,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve documents",
                "message" => $e->getMessage(),
            ];
        }
    }
}
