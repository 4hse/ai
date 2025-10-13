<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE entity attachments
 */
class EntityAttachmentListTool
{
    /**
     * List 4HSE entity attachments with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterEntityAttachmentId Filter by entity attachment ID
     * @param string|null $filterEntityId Filter by entity ID
     * @param string|null $filterAttachmentId Filter by attachment ID
     * @param string|null $filterAttachmentPath Filter by attachment path
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @return array List of entity attachments with pagination
     */
    #[McpTool(
        name: 'list_4hse_entity_attachments',
        description: 'List entity attachment associations in 4HSE. Use this to find associations between entities and file attachments. Filter by entity ID, attachment ID, attachment path. Requires OAuth2 authentication.'
    )]
    public function listEntityAttachments(
        #[Schema(
            type: 'string',
            description: 'Filter by entity attachment ID (UUID format)'
        )]
        ?string $filterEntityAttachmentId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by entity ID (UUID format)'
        )]
        ?string $filterEntityId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by attachment ID'
        )]
        ?string $filterAttachmentId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by attachment path'
        )]
        ?string $filterAttachmentPath = null,

        #[Schema(
            type: 'integer',
            description: 'Number of results per page',
            minimum: 1,
            maximum: 100
        )]
        int $perPage = 100,

        #[Schema(
            type: 'integer',
            description: 'Page number',
            minimum: 1
        )]
        int $page = 1,

        #[Schema(
            type: 'string',
            description: 'Sort by field',
            enum: ['attachment_path', '-attachment_path']
        )]
        ?string $sort = null
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

            // Build request parameters
            $params = [
                'per-page' => $perPage,
                'page' => $page,
            ];

            // Add filters if provided
            $filter = [];
            if ($filterEntityAttachmentId !== null) {
                $filter['entity_attachment_id'] = $filterEntityAttachmentId;
            }
            if ($filterEntityId !== null) {
                $filter['entity_id'] = $filterEntityId;
            }
            if ($filterAttachmentId !== null) {
                $filter['attachment_id'] = $filterAttachmentId;
            }
            if ($filterAttachmentPath !== null) {
                $filter['attachment_path'] = $filterAttachmentPath;
            }

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params['sort'] = $sort;
            }

            // Fetch entity attachments from 4HSE API
            $result = $client->index('entity-attachment', $params);

            return [
                'success' => true,
                'entity_attachments' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => $filter,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve entity attachments',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
