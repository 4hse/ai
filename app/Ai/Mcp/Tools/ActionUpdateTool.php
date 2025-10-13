<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE action
 */
class ActionUpdateTool
{
    /**
     * Update an existing action in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param int $id Action ID
     * @param string|null $actionType Action type (TRAINING, MAINTENANCE, HEALTH, CHECK, PER)
     * @param string|null $name Action name
     * @param string|null $code Action code
     * @param string|null $description Action description
     * @param string|null $validityUnit Validity unit (YEAR, MONTH, DAY)
     * @param int|null $validity Validity period
     * @param int|null $expireInterval Expiration interval
     * @param string|null $manager Manager (JSON format)
     * @param string|null $assignee Assignee (JSON format)
     * @param string|null $watcher Watcher (JSON format)
     * @param string|null $data Additional data (JSON format)
     * @param string|null $subtenantId Subtenant ID (UUID)
     * @param string|null $tenantId Tenant ID (UUID)
     * @return array Updated action details
     */
    #[
        McpTool(
            name: "update_4hse_action",
            description: "Updates an existing action in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateAction(
        #[Schema(type: "integer", description: "Action ID (required)")] int $id,

        #[
            Schema(
                type: "string",
                description: "Action type",
                enum: ["TRAINING", "MAINTENANCE", "HEALTH", "CHECK", "PER"],
            ),
        ]
        ?string $actionType = null,

        #[
            Schema(type: "string", description: "Action name"),
        ]
        ?string $name = null,

        #[
            Schema(type: "string", description: "Action code"),
        ]
        ?string $code = null,

        #[
            Schema(type: "string", description: "Action description"),
        ]
        ?string $description = null,

        #[
            Schema(
                type: "string",
                description: "Validity unit",
                enum: ["YEAR", "MONTH", "DAY"],
            ),
        ]
        ?string $validityUnit = null,

        #[
            Schema(type: "integer", description: "Validity period"),
        ]
        ?int $validity = null,

        #[
            Schema(type: "integer", description: "Expiration interval"),
        ]
        ?int $expireInterval = null,

        #[
            Schema(type: "string", description: "Manager (JSON format)"),
        ]
        ?string $manager = null,

        #[
            Schema(type: "string", description: "Assignee (JSON format)"),
        ]
        ?string $assignee = null,

        #[
            Schema(type: "string", description: "Watcher (JSON format)"),
        ]
        ?string $watcher = null,

        #[
            Schema(
                type: "string",
                description: "Additional data (JSON format)",
            ),
        ]
        ?string $data = null,

        #[
            Schema(type: "string", description: "Subtenant ID in UUID format"),
        ]
        ?string $subtenantId = null,

        #[
            Schema(type: "string", description: "Tenant ID in UUID format"),
        ]
        ?string $tenantId = null,
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

            // Build action data with only provided fields
            $actionData = [];

            if ($actionType !== null) {
                $actionData["action_type"] = $actionType;
            }
            if ($name !== null) {
                $actionData["name"] = $name;
            }
            if ($code !== null) {
                $actionData["code"] = $code;
            }
            if ($description !== null) {
                $actionData["description"] = $description;
            }
            if ($validityUnit !== null) {
                $actionData["validity_unit"] = $validityUnit;
            }
            if ($validity !== null) {
                $actionData["validity"] = $validity;
            }
            if ($expireInterval !== null) {
                $actionData["expire_interval"] = $expireInterval;
            }
            if ($manager !== null) {
                $actionData["manager"] = $manager;
            }
            if ($assignee !== null) {
                $actionData["assignee"] = $assignee;
            }
            if ($watcher !== null) {
                $actionData["watcher"] = $watcher;
            }
            if ($data !== null) {
                $actionData["data"] = $data;
            }
            if ($subtenantId !== null) {
                $actionData["subtenant_id"] = $subtenantId;
            }
            if ($tenantId !== null) {
                $actionData["tenant_id"] = $tenantId;
            }

            // Update action via 4HSE API
            $action = $client->update("action", $id, $actionData);

            return [
                "success" => true,
                "action" => $action,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update action",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
