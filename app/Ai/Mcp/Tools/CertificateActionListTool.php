<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE certificate actions
 */
class CertificateActionListTool
{
    /**
     * List 4HSE certificate actions with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterCertificateActionId Filter by certificate action ID
     * @param string|null $filterCertificateId Filter by certificate ID
     * @param string|null $filterActionId Filter by action ID
     * @param string|null $filterActionName Filter by action name
     * @param string|null $filterActionCode Filter by action code
     * @param string|null $filterActionType Filter by action type
     * @param string|null $filterResourceId Filter by resource ID
     * @param string|null $filterCertificateName Filter by certificate name
     * @param string|null $filterOfficeName Filter by office name
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicized items (default: false)
     * @return array List of certificate actions with pagination
     */
    #[McpTool(
        name: 'list_4hse_certificate_actions',
        description: 'List certificate-action associations in 4HSE. Use this to find which actions are linked to certificates, filter by certificate name, action name, action type (TRAINING, HEALTH, MAINTENANCE, CHECK, PER), resource type, office. View expiration dates and inherited dates. Requires OAuth2 authentication.'
    )]
    public function listCertificateActions(
        #[Schema(
            type: 'string',
            description: 'Filter by certificate action ID (UUID format)'
        )]
        ?string $filterCertificateActionId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by certificate ID (UUID format)'
        )]
        ?string $filterCertificateId = null,

        #[Schema(
            type: 'integer',
            description: 'Filter by action ID'
        )]
        ?int $filterActionId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by action name'
        )]
        ?string $filterActionName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by action code'
        )]
        ?string $filterActionCode = null,

        #[Schema(
            type: 'string',
            description: 'Filter by action type',
            enum: ['TRAINING', 'MAINTENANCE', 'HEALTH', 'CHECK', 'PER']
        )]
        ?string $filterActionType = null,

        #[Schema(
            type: 'string',
            description: 'Filter by resource ID',
            enum: ['PERSON', 'MATERIAL_ITEM', 'ROLE', 'WORK_GROUP', 'WORK_ENVIRONMENT', 'SUBSTANCE', 'EQUIPMENT']
        )]
        ?string $filterResourceId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by certificate name'
        )]
        ?string $filterCertificateName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by office name'
        )]
        ?string $filterOfficeName = null,

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
            enum: ['date_expire', '-date_expire', 'action_name', '-action_name', 'certificate_name', '-certificate_name']
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
            if ($filterCertificateActionId !== null) {
                $filter['certificate_action_id'] = $filterCertificateActionId;
            }
            if ($filterCertificateId !== null) {
                $filter['certificate_id'] = $filterCertificateId;
            }
            if ($filterActionId !== null) {
                $filter['action_id'] = $filterActionId;
            }
            if ($filterActionName !== null) {
                $filter['action_name'] = $filterActionName;
            }
            if ($filterActionCode !== null) {
                $filter['action_code'] = $filterActionCode;
            }
            if ($filterActionType !== null) {
                $filter['action_type'] = $filterActionType;
            }
            if ($filterResourceId !== null) {
                $filter['resource_id'] = $filterResourceId;
            }
            if ($filterCertificateName !== null) {
                $filter['certificate_name'] = $filterCertificateName;
            }
            if ($filterOfficeName !== null) {
                $filter['office_name'] = $filterOfficeName;
            }

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params['sort'] = $sort;
            }

            // Fetch certificate actions from 4HSE API
            $result = $client->index('certificate-action', $params);

            return [
                'success' => true,
                'certificate_actions' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => $filter,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve certificate actions',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
