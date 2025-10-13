<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE substance
 */
class SubstanceViewTool
{
    /**
     * Get a single substance by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Substance ID (UUID)
     * @return array Substance details
     */
    #[McpTool(
        name: 'view_4hse_substance',
        description: 'Retrieves a single 4HSE substance by ID. View complete substance details including name, code, description, office, project information. Requires OAuth2 authentication.'
    )]
    public function viewSubstance(
        #[Schema(
            type: 'string',
            description: 'Substance ID (UUID format)'
        )]
        string $id
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

            // Fetch substance from 4HSE API
            $substance = $client->view('substance', $id);

            return [
                'success' => true,
                'substance' => $substance,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve substance',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
