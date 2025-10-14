<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE projects
 *
 * AI BEHAVIOR REMINDER:
 * - ALWAYS use this tool FIRST when user mentions project names
 * - Search by filterName instead of asking user for project IDs
 * - Use partial matches: "Progetto Test" will find "Progetto Test Ai"
 * - Never ask "What's your project ID?" - ask "What's your project name?"
 */
class ProjectsListTool
{
    /**
     * List 4HSE projects with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterName Filter projects by name (partial match)
     * @param string|null $filterStatus Filter by status: active, suspended, deleted
     * @param string|null $filterProjectType Filter by type: safety, template
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field (e.g., "name", "-created_at" for reverse)
     * @return array List of projects with pagination info
     */
    #[
        McpTool(
            name: "list_4hse_projects",
            description: "Search and find 4HSE projects by name. ALWAYS use this tool first when you need a project ID - search by project name instead of asking the user for IDs. Use this to find projects like 'Progetto Test Ai', 'MyCompany', etc. Filter by name to get project details including IDs, tenant info, and office associations. Requires OAuth2 authentication.",
        ),
    ]
    public function listProjects(
        #[
            Schema(
                type: "string",
                description: "Filter projects by name (partial match) - use this to search for projects like 'Progetto Test Ai', 'MyCompany', etc. instead of asking user for project IDs",
            ),
        ]
        ?string $filterName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by status",
                enum: ["active", "suspended", "deleted"],
            ),
        ]
        ?string $filterStatus = null,

        #[
            Schema(
                type: "string",
                description: "Filter by project type",
                enum: ["safety", "template"],
            ),
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
                description: 'Sort by field (e.g., "name", "-created_at" for reverse order)',
            ),
        ]
        ?string $sort = null,
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
            ];

            // Add filters if provided
            $filter = [];
            if ($filterName !== null) {
                $filter["name"] = $filterName;
            }
            if ($filterStatus !== null) {
                $filter["status"] = $filterStatus;
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

            // Fetch projects from 4HSE API using generic index method
            $result = $client->index("project", $params);

            return [
                "success" => true,
                "projects" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => [
                    "name" => $filterName,
                    "status" => $filterStatus,
                    "project_type" => $filterProjectType,
                ],
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve projects",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
