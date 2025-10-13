<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE work group entities
 */
class WorkGroupEntityListTool
{
    /**
     * List 4HSE work group entities with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterWorkGroupEntityId Filter by work group entity ID
     * @param string|null $filterWorkGroupId Filter by work group ID
     * @param string|null $filterEntityId Filter by entity ID
     * @param string|null $filterEntityType Filter by entity type
     * @param string|null $filterWorkGroupCode Filter by work group code
     * @param string|null $filterWorkGroupName Filter by work group name
     * @param string|null $filterWorkGroupType Filter by work group type
     * @param string|null $filterEntityName Filter by entity name
     * @param string|null $filterEntityCode Filter by entity code
     * @param string|null $filterOfficeName Filter by office name
     * @param string|null $filterOfficeCode Filter by office code
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicist items (default: false)
     * @return array List of work group entities with pagination
     */
    #[
        McpTool(
            name: "list_4hse_work_group_entities",
            description: "List work group entity associations in 4HSE. Use this to find associations between work groups and entities (equipment, work environments, substances). Filter by work group, entity type, office, project. Requires OAuth2 authentication.",
        ),
    ]
    public function listWorkGroupEntities(
        #[
            Schema(
                type: "string",
                description: "Filter by work group entity ID (UUID format)",
            ),
        ]
        ?string $filterWorkGroupEntityId = null,

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
                description: "Filter by entity ID (UUID format)",
            ),
        ]
        ?string $filterEntityId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by entity type",
                enum: ["EQUIPMENT", "WORK_ENVIRONMENT", "SUBSTANCE"],
            ),
        ]
        ?string $filterEntityType = null,

        #[
            Schema(type: "string", description: "Filter by work group code"),
        ]
        ?string $filterWorkGroupCode = null,

        #[
            Schema(type: "string", description: "Filter by work group name"),
        ]
        ?string $filterWorkGroupName = null,

        #[
            Schema(type: "string", description: "Filter by work group type"),
        ]
        ?string $filterWorkGroupType = null,

        #[
            Schema(type: "string", description: "Filter by entity name"),
        ]
        ?string $filterEntityName = null,

        #[
            Schema(type: "string", description: "Filter by entity code"),
        ]
        ?string $filterEntityCode = null,

        #[
            Schema(type: "string", description: "Filter by office name"),
        ]
        ?string $filterOfficeName = null,

        #[
            Schema(type: "string", description: "Filter by office code"),
        ]
        ?string $filterOfficeCode = null,

        #[
            Schema(type: "string", description: "Filter by project name"),
        ]
        ?string $filterProjectName = null,

        #[
            Schema(type: "string", description: "Filter by project type"),
        ]
        ?string $filterProjectType = null,

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
                    "work_group_code",
                    "-work_group_code",
                    "work_group_name",
                    "-work_group_name",
                    "work_group_type",
                    "-work_group_type",
                    "entity_name",
                    "-entity_name",
                    "entity_code",
                    "-entity_code",
                    "entity_type",
                    "-entity_type",
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
            if ($filterWorkGroupEntityId !== null) {
                $filter["work_group_entity_id"] = $filterWorkGroupEntityId;
            }
            if ($filterWorkGroupId !== null) {
                $filter["work_group_id"] = $filterWorkGroupId;
            }
            if ($filterEntityId !== null) {
                $filter["entity_id"] = $filterEntityId;
            }
            if ($filterEntityType !== null) {
                $filter["entity_type"] = $filterEntityType;
            }
            if ($filterWorkGroupCode !== null) {
                $filter["work_group_code"] = $filterWorkGroupCode;
            }
            if ($filterWorkGroupName !== null) {
                $filter["work_group_name"] = $filterWorkGroupName;
            }
            if ($filterWorkGroupType !== null) {
                $filter["work_group_type"] = $filterWorkGroupType;
            }
            if ($filterEntityName !== null) {
                $filter["entity_name"] = $filterEntityName;
            }
            if ($filterEntityCode !== null) {
                $filter["entity_code"] = $filterEntityCode;
            }
            if ($filterOfficeName !== null) {
                $filter["office_name"] = $filterOfficeName;
            }
            if ($filterOfficeCode !== null) {
                $filter["office_code"] = $filterOfficeCode;
            }
            if ($filterProjectName !== null) {
                $filter["project_name"] = $filterProjectName;
            }
            if ($filterProjectType !== null) {
                $filter["project_type"] = $filterProjectType;
            }

            if (!empty($filter)) {
                $params["filter"] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params["sort"] = $sort;
            }

            // Fetch work group entities from 4HSE API
            $result = $client->index("work-group-entity", $params);

            return [
                "success" => true,
                "work_group_entities" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve work group entities",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
