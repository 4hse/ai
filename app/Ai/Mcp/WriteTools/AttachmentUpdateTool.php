<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE attachment
 */
class AttachmentUpdateTool
{
    /**
     * Update an existing attachment in 4HSE (rename or move).
     * Requires OAuth2 authentication.
     *
     * @param string $id Attachment ID
     * @param string $path New path for the attachment
     * @return array Updated attachment details
     */
    #[
        McpTool(
            name: "update_4hse_attachment",
            description: "Updates an existing attachment in 4HSE by renaming or moving it to a new path. Requires OAuth2 authentication.",
        ),
    ]
    public function updateAttachment(
        #[
            Schema(
                type: "string",
                description: "Attachment ID (UUID format) (required)",
            ),
        ]
        string $id,

        #[
            Schema(
                type: "string",
                description: "New path for the attachment (required)",
            ),
        ]
        string $path,
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

            // Build attachment data
            $attachmentData = [
                "path" => $path,
            ];

            // Update attachment via 4HSE API
            $attachment = $client->update("attachment", $id, $attachmentData);

            return [
                "success" => true,
                "attachment" => $attachment,
            ];
        } catch (Throwable $e) {
            // Handle specific error codes
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            return match ($errorCode) {
                400 => [
                    "error" => "Bad request",
                    "message" => $errorMessage,
                    "code" => $errorCode,
                ],
                404 => [
                    "error" => "Attachment not found",
                    "message" => "The specified attachment does not exist.",
                    "code" => $errorCode,
                ],
                409 => [
                    "error" => "Conflict",
                    "message" => "Destination path already exists.",
                    "code" => $errorCode,
                ],
                500 => [
                    "error" => "Server error",
                    "message" => "Unable to rename file or directory.",
                    "code" => $errorCode,
                ],
                default => [
                    "error" => "Failed to update attachment",
                    "message" => $errorMessage,
                    "code" => $errorCode,
                ],
            };
        }
    }
}
