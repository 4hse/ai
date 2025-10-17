<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE work group entity.
 * Work groups in 4HSE can represent three different organizational concepts:
 * - Homogeneous Groups: groups of similar workers/roles
 * - Work Phase: stages or phases in a work process
 * - Job Role: specific positions or task assignments
 */
class WorkGroupEntityUpdateTool
{
    /**
     * Update an existing work group entity in 4HSE.
     * Work groups can represent different organizational concepts: homogeneous groups of similar workers,
     * work phases in a process, or specific job roles/positions.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work group entity ID (UUID)
     * @param string|null $workGroupId Work group ID (UUID)
     * @param string|null $entityId Entity ID (UUID)
     * @param string|null $entityType Entity type
     * @param string|null $description Work group entity description
     * @param string|null $timeSpentMeasure Time spent measure
     * @param string|null $unitOfMeasureId Unit of measure ID (UUID)
     * @return array Updated work group entity details
     */
    #[
        McpTool(
            name: "update_4hse_work_group_entity",
            description: "Updates an existing work group entity association in 4HSE. Work groups can represent homogeneous groups of similar workers, work phases in a process, or specific job roles/positions. Requires OAuth2 authentication.",
        ),
    ]
    public function updateWorkGroupEntity(
        #[
            Schema(
                type: "string",
                description: "Work group entity ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Work group ID in UUID format"),
        ]
        ?string $workGroupId = null,

        #[
            Schema(type: "string", description: "Entity ID in UUID format"),
        ]
        ?string $entityId = null,

        #[
            Schema(
                type: "string",
                description: "Entity type",
                enum: ["EQUIPMENT", "WORK_ENVIRONMENT", "SUBSTANCE"],
            ),
        ]
        ?string $entityType = null,

        #[
            Schema(
                type: "string",
                description: "Work group entity description",
            ),
        ]
        ?string $description = null,

        #[
            Schema(type: "string", description: "Time spent measure"),
        ]
        ?string $timeSpentMeasure = null,

        #[
            Schema(
                type: "string",
                description: "Unit of measure ID in UUID format",
            ),
        ]
        ?string $unitOfMeasureId = null,
    ): array {
        try {
            // Get bearer token from app container (set by MCP middleware)
            $bearerToken = app()->has("mcp.bearer_token")
                ? app("mcp.bearer_token")
                : null;

            if (!$bearerToken) {
                return [
                    "error" => "Authentication required",
                    "message" =>
                        "This tool requires OAuth2 authentication. The bearer token was not found in the request context.",
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build work group entity data with only provided fields
            $workGroupEntityData = [];

            if ($workGroupId !== null) {
                $workGroupEntityData["work_group_id"] = $workGroupId;
            }
            if ($entityId !== null) {
                $workGroupEntityData["entity_id"] = $entityId;
            }
            if ($entityType !== null) {
                $workGroupEntityData["entity_type"] = $entityType;
            }
            if ($description !== null) {
                $workGroupEntityData["description"] = $description;
            }
            if ($timeSpentMeasure !== null) {
                $workGroupEntityData["time_spent_measure"] = $timeSpentMeasure;
            }
            if ($unitOfMeasureId !== null) {
                $workGroupEntityData["unit_of_measure_id"] = $unitOfMeasureId;
            }

            // Update work group entity via 4HSE API
            $workGroupEntity = $client->update(
                "work-group-entity",
                $id,
                $workGroupEntityData,
            );

            return [
                "success" => true,
                "work_group_entity" => $workGroupEntity,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update work group entity",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
