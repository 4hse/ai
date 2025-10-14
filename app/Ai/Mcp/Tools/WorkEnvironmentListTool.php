<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE work environments
 */
class WorkEnvironmentListTool
{
    /**
     * List 4HSE work environments with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterOfficeWorkEnvironmentId Filter by office work environment ID
     * @param string|null $filterOfficeId Filter by office ID
     * @param string|null $filterWorkEnvironmentId Filter by work environment ID
     * @param string|null $filterCode Filter by work environment code
     * @param string|null $filterName Filter by work environment name
     * @param string|null $filterDescription Filter by work environment description
     * @param string|null $filterProjectId Filter by project ID
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param string|null $filterOfficeName Filter by office name
     * @param string|null $filterCategory Filter by work environment category
     * @param bool|null $filterOwnedActive Filter by owned active status
     * @param bool|null $filterParentActive Filter by parent active status
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicist items (default: false)
     * @return array List of work environments with pagination
     */
    #[
        McpTool(
            name: "list_4hse_work_environments",
            description: "Search and find 4HSE work environments by name, code, or description. ALWAYS use this tool first when you need a work environment ID - search by work environment name, code, or description instead of asking the user for work environment IDs. Use this to find work environments like 'Ufficio', 'Magazzino', 'Laboratorio', 'Cantiere', 'Reparto produzione', etc. Filter by name, code, description, category, office, or project to get work environment details including IDs and associations. Requires OAuth2 authentication.",
        ),
    ]
    public function listWorkEnvironments(
        #[
            Schema(
                type: "string",
                description: "Filter by office work environment ID (UUID format)",
            ),
        ]
        ?string $filterOfficeWorkEnvironmentId = null,

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
                description: "Filter by work environment ID (UUID format)",
            ),
        ]
        ?string $filterWorkEnvironmentId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by work environment code - use this to search for work environments by code instead of asking user for work environment IDs",
            ),
        ]
        ?string $filterCode = null,

        #[
            Schema(
                type: "string",
                description: "Filter by work environment name - use this to search for work environments like 'Ufficio', 'Magazzino', 'Laboratorio', 'Cantiere', 'Reparto produzione', etc. instead of asking user for work environment IDs",
            ),
        ]
        ?string $filterName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by work environment description - use this to search for work environments by description instead of asking user for work environment IDs",
            ),
        ]
        ?string $filterDescription = null,

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
                type: "string",
                description: "Filter by work environment category",
            ),
        ]
        ?string $filterCategory = null,

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
                description: "Sort by field (use minus prefix for descending order, e.g., -name)",
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
            if ($filterOfficeWorkEnvironmentId !== null) {
                $filter[
                    "office_work_environment_id"
                ] = $filterOfficeWorkEnvironmentId;
            }
            if ($filterOfficeId !== null) {
                $filter["office_id"] = $filterOfficeId;
            }
            if ($filterWorkEnvironmentId !== null) {
                $filter["work_environment_id"] = $filterWorkEnvironmentId;
            }
            if ($filterCode !== null) {
                $filter["code"] = $filterCode;
            }
            if ($filterName !== null) {
                $filter["name"] = $filterName;
            }
            if ($filterDescription !== null) {
                $filter["description"] = $filterDescription;
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
            if ($filterCategory !== null) {
                $filter["category"] = $filterCategory;
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

            // Fetch work environments from 4HSE API
            $result = $client->index("work-environment", $params);

            return [
                "success" => true,
                "work_environments" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve work environments",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
