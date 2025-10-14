<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE offices
 */
class OfficeListTool
{
    /**
     * List 4HSE offices with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterOfficeId Filter by office ID
     * @param string|null $filterProjectId Filter by project ID
     * @param string|null $filterName Filter by office name
     * @param string|null $filterStreet Filter by street
     * @param string|null $filterPostalCode Filter by postal code
     * @param string|null $filterRegion Filter by region
     * @param string|null $filterLocality Filter by locality
     * @param string|null $filterCountry Filter by country
     * @param string|null $filterTaxCode Filter by tax code
     * @param string|null $filterVat Filter by VAT number
     * @param string|null $filterOfficeTypeId Filter by office type ID
     * @param string|null $filterCode Filter by office code
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicized items (default: false)
     * @return array List of offices with pagination
     */
    #[
        McpTool(
            name: "list_4hse_offices",
            description: "Search and find company offices and locations in 4HSE by name. ALWAYS use this tool first when you need an office/location ID - search by office name instead of asking the user for IDs. Use this to find offices like 'Milano', 'Roma', 'Sede Centrale', etc. Filter by office name, project name, or location details to get office information including IDs and associations. Requires OAuth2 authentication.",
        ),
    ]
    public function listOffices(
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
                description: "Filter by office name - use this to search for offices like 'Milano', 'Roma', 'Sede Centrale', etc. instead of asking user for office IDs",
            ),
        ]
        ?string $filterName = null,

        #[
            Schema(type: "string", description: "Filter by street address"),
        ]
        ?string $filterStreet = null,

        #[
            Schema(type: "string", description: "Filter by postal code"),
        ]
        ?string $filterPostalCode = null,

        #[
            Schema(type: "string", description: "Filter by region"),
        ]
        ?string $filterRegion = null,

        #[
            Schema(type: "string", description: "Filter by locality/city"),
        ]
        ?string $filterLocality = null,

        #[
            Schema(type: "string", description: "Filter by country"),
        ]
        ?string $filterCountry = null,

        #[
            Schema(type: "string", description: "Filter by tax code"),
        ]
        ?string $filterTaxCode = null,

        #[
            Schema(type: "string", description: "Filter by VAT number"),
        ]
        ?string $filterVat = null,

        #[
            Schema(
                type: "string",
                description: "Filter by office type ID (UUID format)",
            ),
        ]
        ?string $filterOfficeTypeId = null,

        #[
            Schema(type: "string", description: "Filter by office code"),
        ]
        ?string $filterCode = null,

        #[
            Schema(
                type: "string",
                description: "Filter by project name - use this to find offices within a specific project like 'Progetto Test Ai', 'MyCompany', etc.",
            ),
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
                description: "Sort by field",
                enum: ["name", "-name", "code", "-code"],
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
            if ($filterOfficeId !== null) {
                $filter["office_id"] = $filterOfficeId;
            }
            if ($filterProjectId !== null) {
                $filter["project_id"] = $filterProjectId;
            }
            if ($filterName !== null) {
                $filter["name"] = $filterName;
            }
            if ($filterStreet !== null) {
                $filter["street"] = $filterStreet;
            }
            if ($filterPostalCode !== null) {
                $filter["postal_code"] = $filterPostalCode;
            }
            if ($filterRegion !== null) {
                $filter["region"] = $filterRegion;
            }
            if ($filterLocality !== null) {
                $filter["locality"] = $filterLocality;
            }
            if ($filterCountry !== null) {
                $filter["country"] = $filterCountry;
            }
            if ($filterTaxCode !== null) {
                $filter["tax_code"] = $filterTaxCode;
            }
            if ($filterVat !== null) {
                $filter["vat"] = $filterVat;
            }
            if ($filterOfficeTypeId !== null) {
                $filter["office_type_id"] = $filterOfficeTypeId;
            }
            if ($filterCode !== null) {
                $filter["code"] = $filterCode;
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

            // Fetch offices from 4HSE API
            $result = $client->index("office", $params);

            return [
                "success" => true,
                "offices" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve offices",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
