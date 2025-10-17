<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE attachment
 */
class AttachmentCreateTool
{
    /**
     * Create a new attachment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $path The file or directory path
     * @param string|null $fileContent Base64 encoded file content (if creating a file)
     * @param int $replace Whether to replace existing file (1 = replace, 0 = don't replace)
     * @return array Created attachment details
     */
    #[
        McpTool(
            name: "create_4hse_attachment",
            description: "Creates a new attachment (file or directory) in 4HSE. If fileContent is provided, creates a file; otherwise creates a directory. Requires OAuth2 authentication.",
        ),
    ]
    public function createAttachment(
        #[
            Schema(
                type: "string",
                description: "The file or directory path (required)",
            ),
        ]
        string $path,

        #[
            Schema(
                type: "string",
                description: "Base64 encoded file content. If empty or null, creates a directory instead of a file.",
            ),
        ]
        ?string $fileContent = null,

        #[
            Schema(
                type: "integer",
                description: 'Replace existing file (1 = replace, 0 = don\'t replace). Ignored when creating directories.',
                minimum: 0,
                maximum: 1,
            ),
        ]
        int $replace = 0,
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

            // Prepare multipart form data
            $formData = [];

            // Add path field
            $formData[] = [
                "name" => "path",
                "contents" => $path,
            ];

            // Add replace field
            $formData[] = [
                "name" => "replace",
                "contents" => (string) $replace,
            ];

            // Add file content if provided
            if ($fileContent !== null && $fileContent !== "") {
                // Decode base64 content
                $decodedContent = base64_decode($fileContent);
                if ($decodedContent === false) {
                    return [
                        "error" => "Invalid file content",
                        "message" =>
                            "The provided file content is not valid base64 encoded data.",
                    ];
                }

                // Extract filename from path
                $filename = basename($path);

                // Add file as multipart field
                $formData[] = [
                    "name" => "file",
                    "contents" => $decodedContent,
                    "filename" => $filename,
                ];
            }

            // Create attachment via 4HSE API
            $attachment = $client->createMultipart("attachment", $formData);

            return [
                "success" => true,
                "attachment" => $attachment,
                "type" => $fileContent ? "file" : "directory",
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
                409 => [
                    "error" => "Conflict",
                    "message" =>
                        "Destination path already exists. Use replace=1 to overwrite existing files.",
                    "code" => $errorCode,
                ],
                413 => [
                    "error" => "File too large",
                    "message" =>
                        "The file size exceeds the maximum allowed limit.",
                    "code" => $errorCode,
                ],
                500 => [
                    "error" => "Server error",
                    "message" =>
                        "Failed to create attachment for unknown reason.",
                    "code" => $errorCode,
                ],
                default => [
                    "error" => "Failed to create attachment",
                    "message" => $errorMessage,
                    "code" => $errorCode,
                ],
            };
        }
    }
}
