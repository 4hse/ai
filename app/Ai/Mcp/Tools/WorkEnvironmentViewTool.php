<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE work environment
 */
class WorkEnvironmentViewTool
{
    /**
     * Get a single work environment by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work environment ID (UUID)
     * @return array Work environment details
     */
    #[
        McpTool(
            name: "view_4hse_work_environment",
            description: "Retrieves a single 4HSE work environment by ID. View complete work environment details including name, code, description, office, project, category information. Requires OAuth2 authentication.",
        ),
    ]
    public function viewWorkEnvironment(
        #[
            Schema(
                type: "string",
                description: "Work environment ID (UUID format)",
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

            // Fetch work environment from 4HSE API
            $workEnvironment = $client->view("work-environment", $id);

            return [
                "success" => true,
                "work_environment" => $workEnvironment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve work environment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
