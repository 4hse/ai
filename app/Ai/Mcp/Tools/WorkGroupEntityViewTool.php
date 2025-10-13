<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE work group entity
 */
class WorkGroupEntityViewTool
{
    /**
     * Get a single work group entity by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work group entity ID (UUID)
     * @return array Work group entity details
     */
    #[
        McpTool(
            name: "view_4hse_work_group_entity",
            description: "Retrieves a single 4HSE work group entity by ID. View complete work group entity details including association between work groups and entities (equipment, work environments, substances). Requires OAuth2 authentication.",
        ),
    ]
    public function viewWorkGroupEntity(
        #[
            Schema(
                type: "string",
                description: "Work group entity ID (UUID format)",
            ),
        ]
        string $id,
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

            // Fetch work group entity from 4HSE API
            $workGroupEntity = $client->view("work-group-entity", $id);

            return [
                "success" => true,
                "work_group_entity" => $workGroupEntity,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve work group entity",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
