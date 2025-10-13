<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE substance
 */
class SubstanceUpdateTool
{
    /**
     * Update an existing substance in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Substance ID (UUID)
     * @param string|null $name Substance name
     * @param string|null $officeId Office ID (UUID)
     * @param string|null $projectId Project ID (UUID)
     * @param string|null $code Substance code
     * @param string|null $description Substance description
     * @return array Updated substance details
     */
    #[
        McpTool(
            name: "update_4hse_substance",
            description: "Updates an existing substance in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateSubstance(
        #[
            Schema(
                type: "string",
                description: "Substance ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Substance name"),
        ]
        ?string $name = null,

        #[
            Schema(type: "string", description: "Office ID in UUID format"),
        ]
        ?string $officeId = null,

        #[
            Schema(type: "string", description: "Project ID in UUID format"),
        ]
        ?string $projectId = null,

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

            // Build substance data with only provided fields
            $substanceData = [];

            if ($name !== null) {
                $substanceData["name"] = $name;
            }
            if ($officeId !== null) {
                $substanceData["office_id"] = $officeId;
            }
            if ($projectId !== null) {
                $substanceData["project_id"] = $projectId;
            }
            if ($code !== null) {
                $substanceData["code"] = $code;
            }
            if ($description !== null) {
                $substanceData["description"] = $description;
            }

            // Update substance via 4HSE API
            $substance = $client->update("substance", $id, $substanceData);

            return [
                "success" => true,
                "substance" => $substance,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update substance",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
