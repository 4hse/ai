<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE substances
 */
class SubstanceListTool
{
    /**
     * List 4HSE substances with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterOfficeSubstanceId Filter by office substance ID
     * @param string|null $filterCode Filter by substance code
     * @param string|null $filterName Filter by substance name
     * @param string|null $filterDescription Filter by substance description
     * @param string|null $filterOfficeId Filter by office ID
     * @param string|null $filterProjectId Filter by project ID
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param string|null $filterOfficeName Filter by office name
     * @param bool|null $filterOwnedActive Filter by owned active status
     * @param bool|null $filterParentActive Filter by parent active status
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicist items (default: false)
     * @return array List of substances with pagination
     */
    #[
        McpTool(
            name: "list_4hse_substances",
            description: "Search and find 4HSE substances by name, code, or description. ALWAYS use this tool first when you need a substance ID - search by substance name, code, or description instead of asking the user for substance IDs. Use this to find substances like 'Benzina', 'Acido solforico', 'Cloro', 'Ammoniaca', etc. Filter by name, code, description, office, or project to get substance details including IDs and associations. Requires OAuth2 authentication.",
        ),
    ]
    public function listSubstances(
        #[
            Schema(
                type: "string",
                description: "Filter by office substance ID (UUID format)",
            ),
        ]
        ?string $filterOfficeSubstanceId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by substance code - use this to search for substances by code instead of asking user for substance IDs",
            ),
        ]
        ?string $filterCode = null,

        #[
            Schema(
                type: "string",
                description: "Filter by substance name - use this to search for substances like 'Benzina', 'Acido solforico', 'Cloro', 'Ammoniaca', etc. instead of asking user for substance IDs",
            ),
        ]
        ?string $filterName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by substance description - use this to search for substances by description instead of asking user for substance IDs",
            ),
        ]
        ?string $filterDescription = null,

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
            if ($filterOfficeSubstanceId !== null) {
                $filter["office_substance_id"] = $filterOfficeSubstanceId;
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
            if ($filterOfficeId !== null) {
                $filter["office_id"] = $filterOfficeId;
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

            // Fetch substances from 4HSE API
            $result = $client->index("substance", $params);

            return [
                "success" => true,
                "substances" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve substances",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
