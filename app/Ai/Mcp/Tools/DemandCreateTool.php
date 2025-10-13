<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE demand
 */
class DemandCreateTool
{
    /**
     * Create a new demand in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $actionId Action ID (UUID)
     * @param string $actionType Action type
     * @param string $resourceId Resource ID (UUID)
     * @param string $resourceType Resource type
     * @param string|null $data Additional data in JSON format
     * @return array Created demand details
     */
    #[
        McpTool(
            name: "create_4hse_demand",
            description: "Creates a new demand in 4HSE. Links an action with a resource to create a requirement or request. Requires OAuth2 authentication.",
        ),
    ]
    public function createDemand(
        #[
            Schema(
                type: "string",
                description: "Action ID in UUID format (required)",
            ),
        ]
        string $actionId,

        #[
            Schema(
                type: "string",
                description: "Action type (required)",
                enum: ["TRAINING", "MAINTENANCE", "HEALTH", "CHECK", "PER"],
            ),
        ]
        string $actionType,

        #[
            Schema(
                type: "string",
                description: "Resource ID in UUID format (required)",
            ),
        ]
        string $resourceId,

        #[
            Schema(
                type: "string",
                description: "Resource type (required)",
                enum: [
                    "MATERIAL_ITEM",
                    "ROLE",
                    "WORK_GROUP",
                    "WORK_ENVIRONMENT",
                    "SUBSTANCE",
                    "EQUIPMENT",
                ],
            ),
        ]
        string $resourceType,

        #[
            Schema(
                type: "string",
                description: "Additional data in JSON format",
            ),
        ]
        ?string $data = null,
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

            // Build demand data
            $demandData = [
                "action_id" => $actionId,
                "action_type" => $actionType,
                "resource_id" => $resourceId,
                "resource_type" => $resourceType,
            ];

            // Add optional fields if provided
            if ($data !== null) {
                $demandData["data"] = $data;
            }

            // Create demand via 4HSE API
            $demand = $client->create("demand", $demandData);

            return [
                "success" => true,
                "demand" => $demand,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create demand",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
