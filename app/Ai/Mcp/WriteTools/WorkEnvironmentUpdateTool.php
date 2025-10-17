<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE work environment
 */
class WorkEnvironmentUpdateTool
{
    /**
     * Update an existing work environment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work environment ID (UUID)
     * @param string|null $officeId Office ID (UUID)
     * @param string|null $name Work environment name
     * @param string|null $projectId Project ID (UUID)
     * @param string|null $code Work environment code
     * @param string|null $description Work environment description
     * @return array Updated work environment details
     */
    #[
        McpTool(
            name: "update_4hse_work_environment",
            description: "Updates an existing work environment in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateWorkEnvironment(
        #[
            Schema(
                type: "string",
                description: "Work environment ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Office ID in UUID format"),
        ]
        ?string $officeId = null,

        #[
            Schema(type: "string", description: "Work environment name"),
        ]
        ?string $name = null,

        #[
            Schema(type: "string", description: "Project ID in UUID format"),
        ]
        ?string $projectId = null,

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

            // Build work environment data with only provided fields
            $workEnvironmentData = [];

            if ($officeId !== null) {
                $workEnvironmentData["office_id"] = $officeId;
            }
            if ($name !== null) {
                $workEnvironmentData["name"] = $name;
            }
            if ($projectId !== null) {
                $workEnvironmentData["project_id"] = $projectId;
            }
            if ($code !== null) {
                $workEnvironmentData["code"] = $code;
            }
            if ($description !== null) {
                $workEnvironmentData["description"] = $description;
            }

            // Update work environment via 4HSE API
            $workEnvironment = $client->update(
                "work-environment",
                $id,
                $workEnvironmentData,
            );

            return [
                "success" => true,
                "work_environment" => $workEnvironment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update work environment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
