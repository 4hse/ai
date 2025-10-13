<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE entity attachment
 */
class EntityAttachmentViewTool
{
    /**
     * Get a single entity attachment by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Entity attachment ID (UUID)
     * @return array Entity attachment details
     */
    #[
        McpTool(
            name: "view_4hse_entity_attachment",
            description: "Retrieves a single 4HSE entity attachment by ID. View complete entity attachment details including entity ID, attachment ID, and attachment path. Requires OAuth2 authentication.",
        ),
    ]
    public function viewEntityAttachment(
        #[
            Schema(
                type: "string",
                description: "Entity attachment ID (UUID format)",
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

            // Fetch entity attachment from 4HSE API
            $entityAttachment = $client->view("entity-attachment", $id);

            return [
                "success" => true,
                "entity_attachment" => $entityAttachment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve entity attachment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
