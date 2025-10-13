<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE entity attachment
 */
class EntityAttachmentCreateTool
{
    /**
     * Create a new entity attachment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $entityId Entity ID (UUID)
     * @param string $attachmentId Attachment ID
     * @param string $attachmentPath Attachment path
     * @return array Created entity attachment details
     */
    #[
        McpTool(
            name: "create_4hse_entity_attachment",
            description: "Creates a new entity attachment association in 4HSE. Links an entity with a file attachment. Requires OAuth2 authentication.",
        ),
    ]
    public function createEntityAttachment(
        #[
            Schema(
                type: "string",
                description: "Entity ID in UUID format (required)",
            ),
        ]
        string $entityId,

        #[
            Schema(type: "string", description: "Attachment ID (required)"),
        ]
        string $attachmentId,

        #[
            Schema(type: "string", description: "Attachment path (required)"),
        ]
        string $attachmentPath,
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

            // Build entity attachment data
            $entityAttachmentData = [
                "entity_id" => $entityId,
                "attachment_id" => $attachmentId,
                "attachment_path" => $attachmentPath,
            ];

            // Create entity attachment via 4HSE API
            $entityAttachment = $client->create(
                "entity-attachment",
                $entityAttachmentData,
            );

            return [
                "success" => true,
                "entity_attachment" => $entityAttachment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create entity attachment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
