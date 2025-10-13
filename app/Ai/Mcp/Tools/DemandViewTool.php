<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE demand
 */
class DemandViewTool
{
    /**
     * Get a single demand by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Demand ID (UUID)
     * @return array Demand details
     */
    #[McpTool(
        name: 'view_4hse_demand',
        description: 'Retrieves a single 4HSE demand by ID. View complete demand details including action type, resource type, office, project, action and resource information. Requires OAuth2 authentication.'
    )]
    public function viewDemand(
        #[Schema(
            type: 'string',
            description: 'Demand ID (UUID format)'
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

            // Fetch demand from 4HSE API
            $demand = $client->view('demand', $id);

            return [
                'success' => true,
                'demand' => $demand,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve demand',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
