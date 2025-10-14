<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use App\Ai\Mcp\Tools\Utils\WorkGroupTypeMapper;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE work groups.
 * Work groups in 4HSE can represent three different organizational concepts:
 * - Homogeneous Groups: groups of similar workers/roles (HGROUP)
 * - Work Phase: stages or phases in a work process (WORK_PLACE)
 * - Job Role: specific positions or task assignments (JOB)
 */
class WorkGroupListTool
{
    /**
     * List 4HSE work groups with optional filters.
     * Work groups can represent different organizational concepts: homogeneous groups of similar workers,
     * work phases in a process, or specific job roles/positions.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterWorkGroupId Filter by work group ID
     * @param string|null $filterCode Filter by work group code
     * @param string|null $filterName Filter by work group name
     * @param string|null $filterOfficeId Filter by office ID
     * @param string|null $filterWorkGroupType Filter by work group type - accepts Italian or English terms that will be mapped to API enum values: 'Gruppo Omogeneo'/'Homogeneous Group' → HGROUP, 'Fase di Lavoro'/'Work Phase' → WORK_PLACE, 'Mansione'/'Job' → JOB
     * @param string|null $filterProjectId Filter by project ID
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param string|null $filterOfficeName Filter by office name
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicist items (default: false)
     * @return array List of work groups with pagination
     */
    #[
        McpTool(
            name: "list_4hse_work_groups",
            description: "Search and find 4HSE work groups by name, code, or type. ALWAYS use this tool first when you need a work group ID - search by work group name, code, or type instead of asking the user for work group IDs. Work groups can represent homogeneous groups of similar workers, work phases in a process, or specific job roles/positions. Use this to find work groups like 'Operatori di macchina', 'Addetti alla manutenzione', 'Tecnici di laboratorio', etc. Filter by name, code, type, office, or project to get work group details including IDs and associations. Requires OAuth2 authentication.",
        ),
    ]
    public function listWorkGroups(
        #[
            Schema(
                type: "string",
                description: "Filter by work group ID (UUID format)",
            ),
        ]
        ?string $filterWorkGroupId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by work group code - use this to search for work groups by code instead of asking user for work group IDs",
            ),
        ]
        ?string $filterCode = null,

        #[
            Schema(
                type: "string",
                description: "Filter by work group name - use this to search for work groups like 'Operatori di macchina', 'Addetti alla manutenzione', 'Tecnici di laboratorio', etc. instead of asking user for work group IDs",
            ),
        ]
        ?string $filterName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by office ID (UUID format)",
            ),
        ]
        ?string $filterOfficeId = null,

        #[
            Schema(
                type: "string",
                description: WorkGroupTypeMapper::SCHEMA_DESCRIPTION,
            ),
        ]
        ?string $filterWorkGroupType = null,

        #[
            Schema(
                type: "string",
                description: "Filter by project ID (UUID format)",
            ),
        ]
        ?string $filterProjectId = null,

        #[
            Schema(type: "string", description: "Filter by project name"),
        ]
        ?string $filterProjectName = null,

        #[
            Schema(type: "string", description: "Filter by project type"),
        ]
        ?string $filterProjectType = null,

        #[
            Schema(type: "string", description: "Filter by office name"),
        ]
        ?string $filterOfficeName = null,

        #[
            Schema(
                type: "integer",
                description: "Number of results per page",
                minimum: 1,
                maximum: 100,
            ),
        ]
        int $perPage = 100,

        #[
            Schema(type: "integer", description: "Page number", minimum: 1),
        ]
        int $page = 1,

        #[
            Schema(
                type: "string",
                description: "Sort by field",
                enum: [
                    "code",
                    "-code",
                    "name",
                    "-name",
                    "work_group_type",
                    "-work_group_type",
                ],
            ),
        ]
        ?string $sort = null,

        #[
            Schema(
                type: "boolean",
                description: "Include historicist items that are not currently valid",
            ),
        ]
        bool $history = false,
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

            // Build request parameters
            $params = [
                "per-page" => $perPage,
                "page" => $page,
                "history" => $history,
            ];

            // Add filters if provided
            $filter = [];
            if ($filterWorkGroupId !== null) {
                $filter["work_group_id"] = $filterWorkGroupId;
            }
            if ($filterCode !== null) {
                $filter["code"] = $filterCode;
            }
            if ($filterName !== null) {
                $filter["name"] = $filterName;
            }
            if ($filterOfficeId !== null) {
                $filter["office_id"] = $filterOfficeId;
            }
            if ($filterWorkGroupType !== null) {
                // Map work group type to API enum value
                $mappedWorkGroupType = WorkGroupTypeMapper::mapWorkGroupType(
                    $filterWorkGroupType,
                );
                if (!$mappedWorkGroupType) {
                    return [
                        "error" => "Invalid work group type",
                        "message" => WorkGroupTypeMapper::getInvalidTypeErrorMessage(
                            $filterWorkGroupType,
                        ),
                    ];
                }
                $filter["work_group_type"] = $mappedWorkGroupType;
            }
            if ($filterProjectId !== null) {
                $filter["project_id"] = $filterProjectId;
            }
            if ($filterProjectName !== null) {
                $filter["project_name"] = $filterProjectName;
            }
            if ($filterProjectType !== null) {
                $filter["project_type"] = $filterProjectType;
            }
            if ($filterOfficeName !== null) {
                $filter["office_name"] = $filterOfficeName;
            }

            if (!empty($filter)) {
                $params["filter"] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params["sort"] = $sort;
            }

            // Fetch work groups from 4HSE API
            $result = $client->index("work-group", $params);

            return [
                "success" => true,
                "work_groups" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve work groups",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
