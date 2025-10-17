<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for deleting a 4HSE action (training course, maintenance plan, procedure, etc.)
 */
class ActionDeleteTool
{
    /**
     * Delete an action from 4HSE.
     * Actions represent training courses, maintenance plans, procedures, individual protection plans, or health surveillance plans.
     * WARNING: This will permanently remove the action and potentially all related subscriptions and certificates.
     * Requires OAuth2 authentication.
     *
     * @param int $id Action ID - the ID of the training course, maintenance plan, procedure, etc. to delete
     * @param bool $force Force deletion of the entity and all related entities.
     * @return array Deletion result
     */
    #[
        McpTool(
            name: "delete_4hse_action",
            description: "Deletes an action in 4HSE (training course, maintenance plan, procedure, individual protection plan, or health surveillance plan). WARNING: This permanently removes the action and potentially all related subscriptions and certificates. Use with caution. Requires OAuth2 authentication.",
        ),
    ]
    public function deleteAction(
        #[
            Schema(
                type: "integer",
                description: "Action ID - the ID of the training course, maintenance plan, procedure, etc. to delete",
            ),
        ]
        int $id,

        #[
            Schema(
                type: "boolean",
                description: "Force deletion of the entity and all related entities",
            ),
        ]
        bool $force = true,
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

            // Build query parameters
            $queryParams = [];
            if ($force) {
                $queryParams["force"] = "true";
            }

            // Delete action via 4HSE API
            $result = $client->delete("action", $id, $queryParams);

            return [
                "success" => true,
                "message" => "Action deleted successfully",
                "deleted" => $result,
            ];
        } catch (Throwable $e) {
            // Check if this is a 400 error with related entities info
            if ($e->getCode() === 400) {
                return [
                    "error" => "Cannot delete action",
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
                    "hint" =>
                        "The action has related entities. Use force=true to delete all related entities.",
                ];
            }

            return [
                "error" => "Failed to delete action",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
