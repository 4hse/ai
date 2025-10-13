<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for deleting a 4HSE work environment
 */
class WorkEnvironmentDeleteTool
{
    /**
     * Delete a work environment from 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work environment ID (UUID)
     * @param bool $force Force deletion of the entity and all related entities.
     * @return array Deletion result
     */
    #[McpTool(
        name: 'delete_4hse_work_environment',
        description: 'Deletes a work environment in 4HSE. If force=false and the work environment has related entities, returns a list of connected entities that would be deleted. If force=true, deletes the work environment and all related entities. Requires OAuth2 authentication.'
    )]
    public function deleteWorkEnvironment(
        #[Schema(
            type: 'string',
            description: 'Work environment ID (UUID format)'
        )]
        string $id,

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

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build query parameters
            $queryParams = [];
            if ($force) {
                $queryParams['force'] = 'true';
            }

            // Delete work environment via 4HSE API
            $result = $client->delete('work-environment', $id, $queryParams);

            return [
                'success' => true,
                'message' => 'Work environment deleted successfully',
                'deleted' => $result,
            ];

        } catch (Throwable $e) {
            // Check if this is a 400 error with related entities info
            if ($e->getCode() === 400) {
                return [
                    'error' => 'Cannot delete work environment',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'hint' => 'The work environment has related entities. Use force=true to delete all related entities.',
                ];
            }

            return [
                'error' => 'Failed to delete work environment',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
