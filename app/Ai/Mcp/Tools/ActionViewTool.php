<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE action
 */
class ActionViewTool
{
    /**
     * Get a single action by ID.
     * Requires OAuth2 authentication.
     *
     * @param int $id Action ID
     * @return array Action details
     */
    #[
        McpTool(
            name: "view_4hse_action",
            description: "Retrieves a single 4HSE action by ID. Requires OAuth2 authentication.",
        ),
    ]
    public function viewAction(
        #[Schema(type: "integer", description: "Action ID")] int $id,
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

            // Fetch action from 4HSE API
            $action = $client->view("action", $id);

            return [
                "success" => true,
                "action" => $action,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve action",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
