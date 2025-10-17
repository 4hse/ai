<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE work group person
 */
class WorkGroupPersonUpdateTool
{
    /**
     * Update an existing work group person in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work group person ID (UUID)
     * @param string|null $workGroupId Work group ID (UUID)
     * @param string|null $personOfficeId Person office ID (UUID)
     * @param string|null $timeSpentMeasure Time spent measure
     * @param string|null $unitOfMeasureId Unit of measure ID (UUID)
     * @return array Updated work group person details
     */
    #[
        McpTool(
            name: "update_4hse_work_group_person",
            description: "Updates an existing work group person association in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateWorkGroupPerson(
        #[
            Schema(
                type: "string",
                description: "Work group person ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Work group ID in UUID format"),
        ]
        ?string $workGroupId = null,

        #[
            Schema(
                type: "string",
                description: "Person office ID in UUID format",
            ),
        ]
        ?string $personOfficeId = null,

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

            // Build work group person data with only provided fields
            $workGroupPersonData = [];

            if ($workGroupId !== null) {
                $workGroupPersonData["work_group_id"] = $workGroupId;
            }
            if ($personOfficeId !== null) {
                $workGroupPersonData["person_office_id"] = $personOfficeId;
            }
            if ($timeSpentMeasure !== null) {
                $workGroupPersonData["time_spent_measure"] = $timeSpentMeasure;
            }
            if ($unitOfMeasureId !== null) {
                $workGroupPersonData["unit_of_measure_id"] = $unitOfMeasureId;
            }

            // Update work group person via 4HSE API
            $workGroupPerson = $client->update(
                "work-group-person",
                $id,
                $workGroupPersonData,
            );

            return [
                "success" => true,
                "work_group_person" => $workGroupPerson,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update work group person",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
