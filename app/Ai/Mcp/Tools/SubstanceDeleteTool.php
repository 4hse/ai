<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for deleting a 4HSE substance
 */
class SubstanceDeleteTool
{
    /**
     * Delete a substance from 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Substance ID (UUID)
     * @param bool $force Force deletion of the entity and all related entities.
     * @return array Deletion result
     */
    #[McpTool(
        name: 'delete_4hse_substance',
        description: 'Deletes a substance in 4HSE. If force=false and the substance has related entities, returns a list of connected entities that would be deleted. If force=true, deletes the substance and all related entities. Requires OAuth2 authentication.'
    )]
    public function deleteSubstance(
        #[Schema(
            type: 'string',
            description: 'Substance ID (UUID format)'
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

            // Delete substance via 4HSE API
            $result = $client->delete('substance', $id, $queryParams);

            return [
                'success' => true,
                'message' => 'Substance deleted successfully',
                'deleted' => $result,
            ];

        } catch (Throwable $e) {
            // Check if this is a 400 error with related entities info
            if ($e->getCode() === 400) {
                return [
                    'error' => 'Cannot delete substance',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'hint' => 'The substance has related entities. Use force=true to delete all related entities.',
                ];
            }

            return [
                'error' => 'Failed to delete substance',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
