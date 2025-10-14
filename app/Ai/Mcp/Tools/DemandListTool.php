<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE demands (alternative requirement relationships, different from action-subscriptions)
 */
class DemandListTool
{
    /**
     * List 4HSE demands with optional filters.
     * Demands represent specific requirements or requests linking actions to resources.
     * This is a different type of requirement relationship than action-subscriptions.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterDemandId Filter by demand ID
     * @param string|null $filterActionId Filter by action ID
     * @param string|null $filterActionType Filter by action type (TRAINING=training courses, MAINTENANCE=maintenance plans, HEALTH=health surveillance, CHECK=procedures, PER=individual protection plans)
     * @param string|null $filterResourceType Filter by resource type - what type of resource the demand applies to
     * @param string|null $filterResourceType Filter by resource type
     * @param string|null $filterOfficeId Filter by office ID
     * @param string|null $filterProjectId Filter by project ID
     * @param string|null $filterActionCode Filter by action code
     * @param string|null $filterActionName Filter by action name
     * @param string|null $filterResourceCode Filter by resource code
     * @param string|null $filterResourceName Filter by resource name
     * @param bool|null $filterOwnedActive Filter by owned active status
     * @param bool|null $filterParentActive Filter by parent active status
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicized items (default: false)
     * @return array List of demands with pagination
     */
    #[
        McpTool(
            name: "list_4hse_demands",
            description: "Search and find 4HSE demands by action name, resource name, or type. ALWAYS use this tool first when you need demand information - search by action name, resource name, or type instead of asking the user for demand IDs. Demands represent specific requirements or requests linking actions to resources (different from action-subscriptions). Use this to find demands for training courses, maintenance plans, procedures, etc. Filter by action name, resource name, action type (TRAINING, MAINTENANCE, HEALTH, CHECK, PER), resource type, office, or project to get demand details including IDs and associations. Requires OAuth2 authentication.",
        ),
    ]
    public function listDemands(
        #[
            Schema(
                type: "string",
                description: "Filter by demand ID (UUID format)",
            ),
        ]
        ?string $filterDemandId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by action ID (UUID format)",
            ),
        ]
        ?string $filterActionId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by action type",
                enum: ["TRAINING", "MAINTENANCE", "HEALTH", "CHECK", "PER"],
            ),
        ]
        ?string $filterActionType = null,

        #[
            Schema(
                type: "string",
                description: "Filter by resource ID (UUID format)",
            ),
        ]
        ?string $filterResourceId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by resource type",
                enum: [
                    "MATERIAL_ITEM",
                    "ROLE",
                    "WORK_GROUP",
                    "WORK_ENVIRONMENT",
                    "SUBSTANCE",
                    "EQUIPMENT",
                ],
            ),
        ]
        ?string $filterResourceType = null,

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
                description: "Filter by project ID (UUID format)",
            ),
        ]
        ?string $filterProjectId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by action code - use this to search for demands by action code instead of asking user for demand IDs",
            ),
        ]
        ?string $filterActionCode = null,

        #[
            Schema(
                type: "string",
                description: "Filter by action name - use this to search for demands by action name like 'Formazione Generale', 'Primo Soccorso', etc. instead of asking user for demand IDs",
            ),
        ]
        ?string $filterActionName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by resource code - use this to search for demands by resource code instead of asking user for demand IDs",
            ),
        ]
        ?string $filterResourceCode = null,

        #[
            Schema(
                type: "string",
                description: "Filter by resource name - use this to search for demands by resource name instead of asking user for demand IDs",
            ),
        ]
        ?string $filterResourceName = null,

        #[
            Schema(
                type: "boolean",
                description: "Filter by owned active status",
            ),
        ]
        ?bool $filterOwnedActive = null,

        #[
            Schema(
                type: "boolean",
                description: "Filter by parent active status",
            ),
        ]
        ?bool $filterParentActive = null,

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
                description: "Sort by field (use minus prefix for descending order, e.g., -action_name)",
            ),
        ]
        ?string $sort = null,

        #[
            Schema(
                type: "boolean",
                description: "Include historicized items that are not currently valid",
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
            if ($filterDemandId !== null) {
                $filter["demand_id"] = $filterDemandId;
            }
            if ($filterActionId !== null) {
                $filter["action_id"] = $filterActionId;
            }
            if ($filterActionType !== null) {
                $filter["action_type"] = $filterActionType;
            }
            if ($filterResourceId !== null) {
                $filter["resource_id"] = $filterResourceId;
            }
            if ($filterResourceType !== null) {
                $filter["resource_type"] = $filterResourceType;
            }
            if ($filterOfficeId !== null) {
                $filter["office_id"] = $filterOfficeId;
            }
            if ($filterProjectId !== null) {
                $filter["project_id"] = $filterProjectId;
            }
            if ($filterActionCode !== null) {
                $filter["action_code"] = $filterActionCode;
            }
            if ($filterActionName !== null) {
                $filter["action_name"] = $filterActionName;
            }
            if ($filterResourceCode !== null) {
                $filter["resource_code"] = $filterResourceCode;
            }
            if ($filterResourceName !== null) {
                $filter["resource_name"] = $filterResourceName;
            }
            if ($filterOwnedActive !== null) {
                $filter["owned_active"] = $filterOwnedActive;
            }
            if ($filterParentActive !== null) {
                $filter["parent_active"] = $filterParentActive;
            }

            if (!empty($filter)) {
                $params["filter"] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params["sort"] = $sort;
            }

            // Fetch demands from 4HSE API
            $result = $client->index("demand", $params);

            return [
                "success" => true,
                "demands" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve demands",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
