<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE person
 */
class PersonViewTool
{
    /**
     * Get a single person by ID or by code+project_id.
     * Requires OAuth2 authentication.
     *
     * @param string|null $id Person ID (UUID). Required if code and projectId are not provided.
     * @param string|null $code Person code. Required together with projectId if id is not provided.
     * @param string|null $projectId Project ID (UUID). Required together with code if id is not provided.
     * @return array Person details
     */
    #[McpTool(
        name: 'view_4hse_person',
        description: 'Retrieves a single 4HSE person by ID or by code+project_id. Requires OAuth2 authentication.'
    )]
    public function viewPerson(
        #[Schema(
            type: 'string',
            description: 'Person ID (UUID format). Required if code and projectId are not provided.'
        )]
        ?string $id = null,

        #[Schema(
            type: 'string',
            description: 'Person code. Required together with projectId if id is not provided.'
        )]
        ?string $code = null,

        #[Schema(
            type: 'string',
            description: 'Project ID (UUID format). Required together with code if id is not provided.'
        )]
        ?string $projectId = null
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

            // Validate parameters
            if (!$id && (!$code || !$projectId)) {
                return [
                    'error' => 'Invalid parameters',
                    'message' => 'Either id must be provided, or both code and projectId must be provided.',
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build query parameters for alternative lookup
            $params = [];
            if (!$id) {
                $params['code'] = $code;
                $params['project_id'] = $projectId;
            }

            // Fetch person from 4HSE API
            // If no ID, pass empty string as the view method will use query params
            $person = $client->view('person', $id ?? '', $params);

            return [
                'success' => true,
                'person' => $person,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve person',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
