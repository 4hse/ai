<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE attachment
 */
class AttachmentViewTool
{
    /**
     * Get a single attachment by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Attachment ID
     * @param string $mode Fetch mode (download or inline)
     * @return array Attachment details
     */
    #[McpTool(
        name: 'view_4hse_attachment',
        description: 'Retrieves a single 4HSE attachment by ID. View complete attachment details including path, metadata, file size, mimetype, and associated project information. Requires OAuth2 authentication.'
    )]
    public function viewAttachment(
        #[Schema(
            type: 'string',
            description: 'Attachment ID (UUID format)'
        )]
        string $id,

        #[Schema(
            type: 'string',
            description: 'Fetch mode for the attachment',
            enum: ['download', 'inline']
        )]
        string $mode = 'download'
    ): array {
        try {
            // Get bearer token from app container (set by MCP middleware)
            $bearerToken = app()->has('mcp.bearer_token') ? app('mcp.bearer_token') : null;

            if (!$bearerToken) {
                return [
                    'error' => 'Authentication required',
                    'message' => 'This tool requires OAuth2 authentication. The bearer token was not found in the request context.',
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build query parameters
            $queryParams = [];
            if ($mode !== 'download') {
                $queryParams['mode'] = $mode;
            }

            // Fetch attachment from 4HSE API
            $attachment = $client->view('attachment', $id, $queryParams);

            return [
                'success' => true,
                'attachment' => $attachment,
                'mode' => $mode,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve attachment',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
