<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE work environment
 */
class WorkEnvironmentCreateTool
{
    /**
     * Create a new work environment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $officeId Office ID (UUID)
     * @param string $name Work environment name
     * @param string $projectId Project ID (UUID)
     * @param string|null $code Work environment code
     * @param string|null $description Work environment description
     * @return array Created work environment details
     */
    #[
        McpTool(
            name: "create_4hse_work_environment",
            description: "Creates a new work environment in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function createWorkEnvironment(
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
                description: "Work environment name (required)",
            ),
        ]
        string $name,

        #[
            Schema(
                type: "string",
                description: "Project ID in UUID format (required)",
            ),
        ]
        string $projectId,

        #[
            Schema(type: "string", description: "Work environment code"),
        ]
        ?string $code = null,

        #[
            Schema(type: "string", description: "Work environment description"),
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

            // Build work environment data
            $workEnvironmentData = [
                "office_id" => $officeId,
                "name" => $name,
                "project_id" => $projectId,
            ];

            // Add optional fields if provided
            if ($code !== null) {
                $workEnvironmentData["code"] = $code;
            }
            if ($description !== null) {
                $workEnvironmentData["description"] = $description;
            }

            // Create work environment via 4HSE API
            $workEnvironment = $client->create(
                "work-environment",
                $workEnvironmentData,
            );

            return [
                "success" => true,
                "work_environment" => $workEnvironment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create work environment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
