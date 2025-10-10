<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE certificates
 */
class CertificateListTool
{
    /**
     * List 4HSE certificates with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterName Filter by certificate name
     * @param string|null $filterActionType Filter by action type
     * @param string|null $filterResourceId Filter by resource ID
     * @param int|null $filterWarning Filter by warning status (0 or 1)
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicized items (default: false)
     * @return array List of certificates with pagination
     */
    #[McpTool(
        name: 'list_4hse_certificates',
        description: 'List certificates (attestati/certificati) for people, equipment, or materials. Use this to find certificates by name, action type (TRAINING, HEALTH, MAINTENANCE, CHECK, PER), resource, or to check expired/valid certificates. Filter by certificate name, action type, resource ID, warning status. Requires OAuth2 authentication.'
    )]
    public function listCertificates(
        #[Schema(
            type: 'string',
            description: 'Filter by certificate name'
        )]
        ?string $filterName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by action type',
            enum: ['TRAINING', 'MAINTENANCE', 'HEALTH', 'CHECK', 'PER']
        )]
        ?string $filterActionType = null,

        #[Schema(
            type: 'string',
            description: 'Filter by resource ID (UUID format or resource type)'
        )]
        ?string $filterResourceId = null,

        #[Schema(
            type: 'integer',
            description: 'Filter by warning status',
            enum: [0, 1]
        )]
        ?int $filterWarning = null,

        #[Schema(
            type: 'integer',
            description: 'Number of results per page',
            minimum: 1,
            maximum: 100
        )]
        int $perPage = 20,

        #[Schema(
            type: 'integer',
            description: 'Page number',
            minimum: 1
        )]
        int $page = 1,

        #[Schema(
            type: 'string',
            description: 'Sort by field',
            enum: ['date_release', '-date_release', 'date_expire', '-date_expire', 'name', '-name', 'action_type', '-action_type']
        )]
        ?string $sort = null,

        #[Schema(
            type: 'boolean',
            description: 'Include historicized items that are not currently valid'
        )]
        bool $history = false
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
                'history' => $history,
            ];

            // Add filters if provided
            $filter = [];
            if ($filterName !== null) {
                $filter['name'] = $filterName;
            }
            if ($filterActionType !== null) {
                $filter['action_type'] = $filterActionType;
            }
            if ($filterResourceId !== null) {
                $filter['resource_id'] = $filterResourceId;
            }
            if ($filterWarning !== null) {
                $filter['warning'] = $filterWarning;
            }

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params['sort'] = $sort;
            }

            // Fetch certificates from 4HSE API
            $result = $client->index('certificate', $params);

            return [
                'success' => true,
                'certificates' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => $filter,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve certificates',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
