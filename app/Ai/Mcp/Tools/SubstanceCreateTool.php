<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE substance
 */
class SubstanceCreateTool
{
    /**
     * Create a new substance in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $name Substance name
     * @param string $officeId Office ID (UUID)
     * @param string $projectId Project ID (UUID)
     * @param string|null $code Substance code
     * @param string|null $description Substance description
     * @return array Created substance details
     */
    #[
        McpTool(
            name: "create_4hse_substance",
            description: "Creates a new substance in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function createSubstance(
        #[
            Schema(type: "string", description: "Substance name (required)"),
        ]
        string $name,

        #[
            Schema(
                type: "string",
                description: "Office ID in UUID format (required)",
            ),
        ]
        string $officeId,

        #[
            Schema(
                type: "string",
                description: "Project ID in UUID format (required)",
            ),
        ]
        string $projectId,

        #[
            Schema(type: "string", description: "Substance code"),
        ]
        ?string $code = null,

        #[
            Schema(type: "string", description: "Substance description"),
        ]
        ?string $description = null,
    ): array {
        try {
            // Get bearer token from app container (set by MCP middleware)
            $bearerToken = app()->has("mcp.bearer_token")
                ? app("mcp.bearer_token")
                : null;

            if (!$bearerToken) {
                return [
                    "error" => "Authentication required",
                    "message" =>
                        "This tool requires OAuth2 authentication. The bearer token was not found in the request context.",
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build substance data
            $substanceData = [
                "name" => $name,
                "office_id" => $officeId,
                "project_id" => $projectId,
            ];

            // Add optional fields if provided
            if ($code !== null) {
                $substanceData["code"] = $code;
            }
            if ($description !== null) {
                $substanceData["description"] = $description;
            }

            // Create substance via 4HSE API
            $substance = $client->create("substance", $substanceData);

            return [
                "success" => true,
                "substance" => $substance,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create substance",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
