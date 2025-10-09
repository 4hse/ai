<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE projects
 */
class ProjectsListTool
{
    /**
     * List 4HSE projects with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterName Filter projects by name (partial match)
     * @param string|null $filterStatus Filter by status: active, suspended, deleted
     * @param string|null $filterProjectType Filter by type: safety, template
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field (e.g., "name", "-created_at" for reverse)
     * @return array List of projects with pagination info
     */
    #[McpTool(
        name: 'list_4hse_projects',
        description: 'Retrieves a paginated list of 4HSE projects with optional filters. Requires OAuth2 authentication.'
    )]
    public function listProjects(
        #[Schema(
            type: 'string',
            description: 'Filter projects by name (partial match)'
        )]
        ?string $filterName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by status',
            enum: ['active', 'suspended', 'deleted']
        )]
        ?string $filterStatus = null,

        #[Schema(
            type: 'string',
            description: 'Filter by project type',
            enum: ['safety', 'template']
        )]
        ?string $filterProjectType = null,

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
            description: 'Sort by field (e.g., "name", "-created_at" for reverse order)'
        )]
        ?string $sort = null
    ): array {
        try {
            // Get bearer token from current request
            $bearerToken = request()->attributes->get('bearer_token');

            if (!$bearerToken) {
                return [
                    'error' => 'Authentication required',
                    'message' => 'This tool requires OAuth2 authentication. Please authenticate first.',
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
            if ($filterName !== null) {
                $filter['name'] = $filterName;
            }
            if ($filterStatus !== null) {
                $filter['status'] = $filterStatus;
            }
            if ($filterProjectType !== null) {
                $filter['project_type'] = $filterProjectType;
            }

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params['sort'] = $sort;
            }

            // Fetch projects from 4HSE API
            $result = $client->getProjects($params);

            return [
                'success' => true,
                'projects' => $result['projects'],
                'pagination' => $result['pagination'],
                'filters_applied' => [
                    'name' => $filterName,
                    'status' => $filterStatus,
                    'project_type' => $filterProjectType,
                ],
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve projects',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
