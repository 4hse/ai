<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE attachments
 */
class AttachmentListTool
{
    /**
     * List 4HSE attachments with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $key Filter key for metadata search
     * @param string|null $value Filter value for metadata search
     * @param string|null $searchText Search text in attachment paths
     * @param string|null $rootPath Filter by root path
     * @param string|null $parentPath Filter by parent path
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @return array List of attachments with pagination
     */
    #[
        McpTool(
            name: "list_4hse_attachments",
            description: "Search and find 4HSE file attachments by filename, path, or metadata. ALWAYS use this tool first when you need attachment information - search by filename, path, or metadata instead of asking the user for attachment IDs. Use this to find attachments like 'documento.pdf', 'certificato.jpg', 'relazione_tecnica.docx', etc. Filter by searchText (filename/path), metadata keys/values, root path, or parent path to get attachment details including IDs and file information. Requires OAuth2 authentication.",
        ),
    ]
    public function listAttachments(
        #[
            Schema(
                type: "string",
                description: 'Filter key for metadata search (e.g., "project_name", "mimetype")',
            ),
        ]
        ?string $key = null,

        #[
            Schema(
                type: "string",
                description: "Filter value for metadata search",
            ),
        ]
        ?string $value = null,

        #[
            Schema(
                type: "string",
                description: "Search text in attachment paths - use this to search for attachments by filename like 'documento.pdf', 'certificato.jpg', 'relazione_tecnica.docx', etc. instead of asking user for attachment IDs",
            ),
        ]
        ?string $searchText = null,

        #[
            Schema(type: "string", description: "Filter by root path"),
        ]
        ?string $rootPath = null,

        #[
            Schema(type: "string", description: "Filter by parent path"),
        ]
        ?string $parentPath = null,

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
                enum: ["attachment_path"],
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
            if ($key !== null) {
                $params["key"] = $key;
            }
            if ($value !== null) {
                $params["value"] = $value;
            }
            if ($searchText !== null) {
                $params["searchText"] = $searchText;
            }
            if ($rootPath !== null) {
                $params["root_path"] = $rootPath;
            }
            if ($parentPath !== null) {
                $params["parent_path"] = $parentPath;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params["sort"] = $sort;
            }

            // Fetch attachments from 4HSE API
            $result = $client->index("attachment", $params);

            return [
                "success" => true,
                "attachments" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => array_filter([
                    "key" => $key,
                    "value" => $value,
                    "searchText" => $searchText,
                    "root_path" => $rootPath,
                    "parent_path" => $parentPath,
                ]),
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve attachments",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
