<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE persons
 */
class PersonListTool
{
    /**
     * List 4HSE persons with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterFirstName Filter persons by first name (partial match)
     * @param string|null $filterLastName Filter persons by last name (partial match)
     * @param string|null $filterCode Filter persons by code
     * @param string|null $filterTaxCode Filter persons by tax code
     * @param string|null $filterEntityId Filter by entity ID
     * @param string|null $filterProjectId Filter by project ID (UUID)
     * @param string|null $filterProjectName Filter by project name
     * @param int|null $filterIsEmployee Filter by employee status (0 or 1)
     * @param int|null $filterIsPreventionPeople Filter by prevention people status (0 or 1)
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field (e.g., "first_name", "-last_name" for reverse)
     * @param bool $history Include historicized persons
     * @return array List of persons with pagination info
     */
    #[McpTool(
        name: 'list_4hse_persons',
        description: 'Retrieves a paginated list of 4HSE persons with optional filters. Requires OAuth2 authentication.'
    )]
    public function listPersons(
        #[Schema(
            type: 'string',
            description: 'Filter persons by first name (partial match)'
        )]
        ?string $filterFirstName = null,

        #[Schema(
            type: 'string',
            description: 'Filter persons by last name (partial match)'
        )]
        ?string $filterLastName = null,

        #[Schema(
            type: 'string',
            description: 'Filter persons by code'
        )]
        ?string $filterCode = null,

        #[Schema(
            type: 'string',
            description: 'Filter persons by tax code'
        )]
        ?string $filterTaxCode = null,

        #[Schema(
            type: 'string',
            description: 'Filter by entity ID'
        )]
        ?string $filterEntityId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by project ID (UUID format)'
        )]
        ?string $filterProjectId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by project name'
        )]
        ?string $filterProjectName = null,

        #[Schema(
            type: 'integer',
            description: 'Filter by employee status',
            enum: [0, 1]
        )]
        ?int $filterIsEmployee = null,

        #[Schema(
            type: 'integer',
            description: 'Filter by prevention people status',
            enum: [0, 1]
        )]
        ?int $filterIsPreventionPeople = null,

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
            description: 'Sort by field (e.g., "first_name", "-last_name" for reverse order)',
            enum: ['first_name', '-first_name', 'last_name', '-last_name', 'code', '-code', 'tax_code', '-tax_code', 'birth_date', '-birth_date']
        )]
        ?string $sort = null,

        #[Schema(
            type: 'boolean',
            description: 'Include historicized persons'
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
            if ($filterFirstName !== null) {
                $filter['first_name'] = $filterFirstName;
            }
            if ($filterLastName !== null) {
                $filter['last_name'] = $filterLastName;
            }
            if ($filterCode !== null) {
                $filter['code'] = $filterCode;
            }
            if ($filterTaxCode !== null) {
                $filter['tax_code'] = $filterTaxCode;
            }
            if ($filterEntityId !== null) {
                $filter['entity_id'] = $filterEntityId;
            }
            if ($filterProjectId !== null) {
                $filter['project_id'] = $filterProjectId;
            }
            if ($filterProjectName !== null) {
                $filter['project_name'] = $filterProjectName;
            }
            if ($filterIsEmployee !== null) {
                $filter['is_employee'] = $filterIsEmployee;
            }
            if ($filterIsPreventionPeople !== null) {
                $filter['is_prevention_people'] = $filterIsPreventionPeople;
            }

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params['sort'] = $sort;
            }

            // Fetch persons from 4HSE API
            $result = $client->index('person', $params);

            return [
                'success' => true,
                'persons' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => [
                    'first_name' => $filterFirstName,
                    'last_name' => $filterLastName,
                    'code' => $filterCode,
                    'tax_code' => $filterTaxCode,
                    'entity_id' => $filterEntityId,
                    'project_id' => $filterProjectId,
                    'project_name' => $filterProjectName,
                    'is_employee' => $filterIsEmployee,
                    'is_prevention_people' => $filterIsPreventionPeople,
                ],
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve persons',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
