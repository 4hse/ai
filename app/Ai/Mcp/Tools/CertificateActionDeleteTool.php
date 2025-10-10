<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for deleting a 4HSE certificate action
 */
class CertificateActionDeleteTool
{
    /**
     * Delete a certificate action from 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Certificate action ID (UUID)
     * @param bool $force Force deletion of the entity and all related entities.
     * @return array Deletion result
     */
    #[McpTool(
        name: 'delete_4hse_certificate_action',
        description: 'Deletes a certificate-action association in 4HSE. Removes the link between a certificate and an action. Requires OAuth2 authentication.'
    )]
    public function deleteCertificateAction(
        #[Schema(
            type: 'string',
            description: 'Certificate action ID (UUID format)'
        )]
        string $id,

        #[Schema(
            type: 'boolean',
            description: 'Force deletion of the entity and all related entities'
        )]
        bool $force = true
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

            // Delete certificate action via 4HSE API
            $result = $client->delete('certificate-action', $id, $queryParams);

            return [
                'success' => true,
                'message' => 'Certificate action deleted successfully',
                'deleted' => $result,
            ];

        } catch (Throwable $e) {
            // Check if this is a 400 error with related entities info
            if ($e->getCode() === 400) {
                return [
                    'error' => 'Cannot delete certificate action',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'hint' => 'The certificate action has related entities. Use force=true to delete all related entities.',
                ];
            }

            return [
                'error' => 'Failed to delete certificate action',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
