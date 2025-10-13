<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE work group
 */
class WorkGroupViewTool
{
    /**
     * Get a single work group by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work group ID (UUID)
     * @return array Work group details
     */
    #[
        McpTool(
            name: "view_4hse_work_group",
            description: "Retrieves a single 4HSE work group by ID. View complete work group details including name, code, description, office, project, type. Requires OAuth2 authentication.",
        ),
    ]
    public function viewWorkGroup(
        #[
            Schema(type: "string", description: "Work group ID (UUID format)"),
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

            // Fetch work group from 4HSE API
            $workGroup = $client->view("work-group", $id);

            return [
                "success" => true,
                "work_group" => $workGroup,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve work group",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
