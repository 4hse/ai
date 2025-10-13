<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE demand
 */
class DemandUpdateTool
{
    /**
     * Update an existing demand in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Demand ID (UUID)
     * @param string|null $actionId Action ID (UUID)
     * @param string|null $actionType Action type
     * @param string|null $resourceId Resource ID (UUID)
     * @param string|null $resourceType Resource type
     * @param string|null $data Additional data in JSON format
     * @return array Updated demand details
     */
    #[McpTool(
        name: 'update_4hse_demand',
        description: 'Updates an existing demand in 4HSE. Requires OAuth2 authentication.'
    )]
    public function updateDemand(
        #[Schema(
            type: 'string',
            description: 'Demand ID in UUID format (required)'
        )]
        string $id,

        #[Schema(
            type: 'string',
            description: 'Action ID in UUID format'
        )]
        ?string $actionId = null,

        #[Schema(
            type: 'string',
            description: 'Action type',
            enum: ['TRAINING', 'MAINTENANCE', 'HEALTH', 'CHECK', 'PER']
        )]
        ?string $actionType = null,

        #[Schema(
            type: 'string',
            description: 'Resource ID in UUID format'
        )]
        ?string $resourceId = null,

        #[Schema(
            type: 'string',
            description: 'Resource type',
            enum: ['MATERIAL_ITEM', 'ROLE', 'WORK_GROUP', 'WORK_ENVIRONMENT', 'SUBSTANCE', 'EQUIPMENT']
        )]
        ?string $resourceType = null,

        #[Schema(
            type: 'string',
            description: 'Additional data in JSON format'
        )]
        ?string $data = null
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

            // Build demand data with only provided fields
            $demandData = [];

            if ($actionId !== null) {
                $demandData['action_id'] = $actionId;
            }
            if ($actionType !== null) {
                $demandData['action_type'] = $actionType;
            }
            if ($resourceId !== null) {
                $demandData['resource_id'] = $resourceId;
            }
            if ($resourceType !== null) {
                $demandData['resource_type'] = $resourceType;
            }
            if ($data !== null) {
                $demandData['data'] = $data;
            }

            // Update demand via 4HSE API
            $demand = $client->update('demand', $id, $demandData);

            return [
                'success' => true,
                'demand' => $demand,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to update demand',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
