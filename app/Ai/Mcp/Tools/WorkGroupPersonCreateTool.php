<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE work group person
 */
class WorkGroupPersonCreateTool
{
    /**
     * Create a new work group person in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $workGroupId Work group ID (UUID)
     * @param string $personOfficeId Person office ID (UUID)
     * @param string|null $timeSpentMeasure Time spent measure
     * @param string|null $unitOfMeasureId Unit of measure ID (UUID)
     * @return array Created work group person details
     */
    #[McpTool(
        name: 'create_4hse_work_group_person',
        description: 'Creates a new work group person association in 4HSE. Associates a person (via person-office) with a work group. Requires OAuth2 authentication.'
    )]
    public function createWorkGroupPerson(
        #[Schema(
            type: 'string',
            description: 'Work group ID in UUID format (required)'
        )]
        string $workGroupId,

        #[Schema(
            type: 'string',
            description: 'Person office ID in UUID format (required)'
        )]
        string $personOfficeId,

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

            // Build work group person data
            $workGroupPersonData = [
                'work_group_id' => $workGroupId,
                'person_office_id' => $personOfficeId,
            ];

            // Add optional fields if provided
            if ($timeSpentMeasure !== null) {
                $workGroupPersonData['time_spent_measure'] = $timeSpentMeasure;
            }
            if ($unitOfMeasureId !== null) {
                $workGroupPersonData['unit_of_measure_id'] = $unitOfMeasureId;
            }

            // Create work group person via 4HSE API
            $workGroupPerson = $client->create('work-group-person', $workGroupPersonData);

            return [
                'success' => true,
                'work_group_person' => $workGroupPerson,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to create work group person',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
