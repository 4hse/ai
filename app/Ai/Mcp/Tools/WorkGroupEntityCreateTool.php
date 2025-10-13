<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE work group entity
 */
class WorkGroupEntityCreateTool
{
    /**
     * Create a new work group entity in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $workGroupId Work group ID (UUID)
     * @param string $entityId Entity ID (UUID)
     * @param string $entityType Entity type
     * @param string|null $description Work group entity description
     * @param string|null $timeSpentMeasure Time spent measure
     * @param string|null $unitOfMeasureId Unit of measure ID (UUID)
     * @return array Created work group entity details
     */
    #[McpTool(
        name: 'create_4hse_work_group_entity',
        description: 'Creates a new work group entity association in 4HSE. Associates a work group with an entity (equipment, work environment, or substance). Requires OAuth2 authentication.'
    )]
    public function createWorkGroupEntity(
        #[Schema(
            type: 'string',
            description: 'Work group ID in UUID format (required)'
        )]
        string $workGroupId,

        #[Schema(
            type: 'string',
            description: 'Entity ID in UUID format (required)'
        )]
        string $entityId,

        #[Schema(
            type: 'string',
            description: 'Entity type (required)',
            enum: ['EQUIPMENT', 'WORK_ENVIRONMENT', 'SUBSTANCE']
        )]
        string $entityType,

        #[Schema(
            type: 'string',
            description: 'Work group entity description'
        )]
        ?string $description = null,

        #[Schema(
            type: 'string',
            description: 'Time spent measure'
        )]
        ?string $timeSpentMeasure = null,

        #[Schema(
            type: 'string',
            description: 'Unit of measure ID in UUID format'
        )]
        ?string $unitOfMeasureId = null
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

            // Build work group entity data
            $workGroupEntityData = [
                'work_group_id' => $workGroupId,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
            ];

            // Add optional fields if provided
            if ($description !== null) {
                $workGroupEntityData['description'] = $description;
            }
            if ($timeSpentMeasure !== null) {
                $workGroupEntityData['time_spent_measure'] = $timeSpentMeasure;
            }
            if ($unitOfMeasureId !== null) {
                $workGroupEntityData['unit_of_measure_id'] = $unitOfMeasureId;
            }

            // Create work group entity via 4HSE API
            $workGroupEntity = $client->create('work-group-entity', $workGroupEntityData);

            return [
                'success' => true,
                'work_group_entity' => $workGroupEntity,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to create work group entity',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
