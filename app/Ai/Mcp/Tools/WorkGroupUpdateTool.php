<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE work group
 */
class WorkGroupUpdateTool
{
    /**
     * Update an existing work group in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work group ID (UUID)
     * @param string|null $name Work group name
     * @param string|null $officeId Office ID (UUID)
     * @param string|null $workGroupType Work group type
     * @param string|null $code Work group code
     * @param string|null $description Work group description
     * @return array Updated work group details
     */
    #[
        McpTool(
            name: "update_4hse_work_group",
            description: "Updates an existing work group in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateWorkGroup(
        #[
            Schema(
                type: "string",
                description: "Work group ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Work group name"),
        ]
        ?string $name = null,

        #[
            Schema(type: "string", description: "Office ID in UUID format"),
        ]
        ?string $officeId = null,

        #[
            Schema(type: "string", description: "Work group type"),
        ]
        ?string $workGroupType = null,

        #[
            Schema(type: "string", description: "Work group code"),
        ]
        ?string $code = null,

        #[
            Schema(type: "string", description: "Work group description"),
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

            // Build work group data with only provided fields
            $workGroupData = [];

            if ($name !== null) {
                $workGroupData["name"] = $name;
            }
            if ($officeId !== null) {
                $workGroupData["office_id"] = $officeId;
            }
            if ($workGroupType !== null) {
                $workGroupData["work_group_type"] = $workGroupType;
            }
            if ($code !== null) {
                $workGroupData["code"] = $code;
            }
            if ($description !== null) {
                $workGroupData["description"] = $description;
            }

            // Update work group via 4HSE API
            $workGroup = $client->update("work-group", $id, $workGroupData);

            return [
                "success" => true,
                "work_group" => $workGroup,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update work group",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
