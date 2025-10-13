<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE actions
 */
class ActionListTool
{
    /**
     * List 4HSE actions with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterActionId Filter by action ID (UUID)
     * @param string|null $filterActionType Filter by action type (TRAINING, MAINTENANCE, HEALTH, CHECK, PER)
     * @param string|null $filterCode Filter by action code
     * @param string|null $filterName Filter by action name
     * @param string|null $filterSubtenantId Filter by subtenant ID (UUID)
     * @param string|null $filterTenantId Filter by tenant ID (UUID)
     * @param string|null $filterOfficeName Filter by office name
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field (e.g., "name", "-code" for reverse)
     * @param bool $history Include historicized items (default: false)
     * @return array List of actions with pagination
     */
    #[
        McpTool(
            name: "list_4hse_actions",
            description: "Retrieves a paginated list of 4HSE actions with optional filters. Requires OAuth2 authentication.",
        ),
    ]
    public function listActions(
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
            Schema(type: "string", description: "Filter by action code"),
        ]
        ?string $filterCode = null,

        #[
            Schema(type: "string", description: "Filter by action name"),
        ]
        ?string $filterName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by subtenant ID (UUID format)",
            ),
        ]
        ?string $filterSubtenantId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by tenant ID (UUID format)",
            ),
        ]
        ?string $filterTenantId = null,

        #[
            Schema(type: "string", description: "Filter by office name"),
        ]
        ?string $filterOfficeName = null,

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
        int $perPage = 20,

        #[
            Schema(type: "integer", description: "Page number", minimum: 1),
        ]
        int $page = 1,

        #[
            Schema(
                type: "string",
                description: 'Sort by field (e.g., "name", "-code" for reverse order)',
                enum: [
                    "code",
                    "-code",
                    "name",
                    "-name",
                    "action_type",
                    "-action_type",
                    "office_name",
                    "-office_name",
                    "project_name",
                    "-project_name",
                ],
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
            if ($filterActionId !== null) {
                $filter["action_id"] = $filterActionId;
            }
            if ($filterActionType !== null) {
                $filter["action_type"] = $filterActionType;
            }
            if ($filterCode !== null) {
                $filter["code"] = $filterCode;
            }
            if ($filterName !== null) {
                $filter["name"] = $filterName;
            }
            if ($filterSubtenantId !== null) {
                $filter["subtenant_id"] = $filterSubtenantId;
            }
            if ($filterTenantId !== null) {
                $filter["tenant_id"] = $filterTenantId;
            }
            if ($filterOfficeName !== null) {
                $filter["office_name"] = $filterOfficeName;
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

            // Fetch actions from 4HSE API
            $result = $client->index("action", $params);

            return [
                "success" => true,
                "actions" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve actions",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
