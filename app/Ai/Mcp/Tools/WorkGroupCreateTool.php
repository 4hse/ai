<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE work group.
 * Work groups in 4HSE can represent three different organizational concepts:
 * - Homogeneous Groups: groups of similar workers/roles
 * - Work Phase: stages or phases in a work process
 * - Job Role: specific positions or task assignments
 */
class WorkGroupCreateTool
{
    /**
     * Create a new work group in 4HSE.
     * Work groups can represent different organizational concepts: homogeneous groups of similar workers,
     * work phases in a process, or specific job roles/positions.
     * Requires OAuth2 authentication.
     *
     * @param string $name Work group name
     * @param string $officeId Office ID (UUID)
     * @param string $workGroupType Work group type - can be one of three types: 'Homogeneous Groups' (groups of similar workers/roles), 'Work Phase' (stage or phase in a work process), or 'Job Role' (specific position or task assignment)
     * @param string|null $code Work group code
     * @param string|null $description Work group description
     * @return array Created work group details
     */
    #[
        McpTool(
            name: "create_4hse_work_group",
            description: "Creates a new work group/team in 4HSE. Work groups can represent homogeneous groups of similar workers, work phases in a process, or specific job roles/positions. Requires OAuth2 authentication.",
        ),
    ]
    public function createWorkGroup(
        #[
            Schema(type: "string", description: "Work group name (required)"),
        ]
        string $name,

        #[
            Schema(
                type: "string",
                description: "Office ID in UUID format (required)",
            ),
        ]
        string $officeId,

        #[
            Schema(
                type: "string",
                description: "Work group type (required). Can be one of three types: 'Homogeneous Groups' (groups of similar workers/roles), 'Work Phase' (stage or phase in a work process), or 'Job Role' (specific position or task assignment)",
            ),
        ]
        string $workGroupType,

        #[
            Schema(type: "string", description: "Work group code"),
        ]
        ?string $code = null,

        #[
            Schema(type: "string", description: "Work group description"),
        ]
        ?string $description = null,
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

            // Build work group data
            $workGroupData = [
                "name" => $name,
                "office_id" => $officeId,
                "work_group_type" => $workGroupType,
            ];

            // Add optional fields if provided
            if ($code !== null) {
                $workGroupData["code"] = $code;
            }
            if ($description !== null) {
                $workGroupData["description"] = $description;
            }

            // Create work group via 4HSE API
            $workGroup = $client->create("work-group", $workGroupData);

            return [
                "success" => true,
                "work_group" => $workGroup,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create work group",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
