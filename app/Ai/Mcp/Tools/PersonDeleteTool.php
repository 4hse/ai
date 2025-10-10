<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for deleting a 4HSE person
 */
class PersonDeleteTool
{
    /**
     * Delete a person from 4HSE by ID or by code+project_id.
     * Requires OAuth2 authentication.
     *
     * @param string|null $id Person ID (UUID). Required if code and projectId are not provided.
     * @param string|null $code Person code. Required together with projectId if id is not provided.
     * @param string|null $projectId Project ID (UUID). Required together with code if id is not provided.
     * @param bool $historicize If true, the person will be historicized instead of deleted.
     * @param bool $force Force deletion of the entity and all related entities.
     * @return array Deletion result
     */
    #[McpTool(
        name: 'delete_4hse_person',
        description: 'Deletes or historicizes a person in 4HSE by ID or by code+project_id. Requires OAuth2 authentication.'
    )]
    public function deletePerson(
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
        ?string $projectId = null,

        #[Schema(
            type: 'boolean',
            description: 'If true, the person will be historicized instead of deleted'
        )]
        bool $historicize = false,

        #[Schema(
            type: 'boolean',
            description: 'Force deletion of the entity and all related entities'
        )]
        bool $force = false
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

            // Build query parameters
            $queryParams = [];
            if ($historicize) {
                $queryParams['historicize'] = 'true';
            }
            if ($force) {
                $queryParams['force'] = 'true';
            }
            if (!$id) {
                $queryParams['code'] = $code;
                $queryParams['project_id'] = $projectId;
            }

            // Build person identifier with query string
            $personIdentifier = $id ?? 'lookup';
            if (!empty($queryParams)) {
                $personIdentifier .= '?' . http_build_query($queryParams);
            }

            // Delete person via 4HSE API
            $result = $client->delete('person', $personIdentifier);

            return [
                'success' => true,
                'message' => $historicize
                    ? 'Person historicized successfully'
                    : 'Person deleted successfully',
                'deleted' => $result,
            ];

        } catch (Throwable $e) {
            // Check if this is a 400 error with related entities info
            if ($e->getCode() === 400) {
                return [
                    'error' => 'Cannot delete person',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'hint' => 'The person has related entities. Use force=true to delete all related entities.',
                ];
            }

            return [
                'error' => 'Failed to delete person',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
