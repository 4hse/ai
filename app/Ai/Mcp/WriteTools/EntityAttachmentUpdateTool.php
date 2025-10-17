<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE entity attachment
 */
class EntityAttachmentUpdateTool
{
    /**
     * Update an existing entity attachment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Entity attachment ID (UUID)
     * @param string|null $entityId Entity ID (UUID)
     * @param string|null $attachmentId Attachment ID
     * @param string|null $attachmentPath Attachment path
     * @return array Updated entity attachment details
     */
    #[
        McpTool(
            name: "update_4hse_entity_attachment",
            description: "Updates an existing entity attachment association in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateEntityAttachment(
        #[
            Schema(
                type: "string",
                description: "Entity attachment ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Entity ID in UUID format"),
        ]
        ?string $entityId = null,

        #[
            Schema(type: "string", description: "Attachment ID"),
        ]
        ?string $attachmentId = null,

        #[
            Schema(type: "string", description: "Attachment path"),
        ]
        ?string $attachmentPath = null,
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

            // Build entity attachment data with only provided fields
            $entityAttachmentData = [];

            if ($entityId !== null) {
                $entityAttachmentData["entity_id"] = $entityId;
            }
            if ($attachmentId !== null) {
                $entityAttachmentData["attachment_id"] = $attachmentId;
            }
            if ($attachmentPath !== null) {
                $entityAttachmentData["attachment_path"] = $attachmentPath;
            }

            // Update entity attachment via 4HSE API
            $entityAttachment = $client->update(
                "entity-attachment",
                $id,
                $entityAttachmentData,
            );

            return [
                "success" => true,
                "entity_attachment" => $entityAttachment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update entity attachment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
