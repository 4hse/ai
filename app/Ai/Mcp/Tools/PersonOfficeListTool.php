<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE person-office associations.
 * This lists persons assigned to specific offices within projects.
 * Note: Persons can exist at the project level without being assigned to specific offices.
 */
class PersonOfficeListTool
{
    /**
     * List 4HSE person-office associations with optional filters.
     * This lists persons assigned to specific offices within projects.
     * Note: Persons can exist at the project level without being assigned to offices.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterPersonOfficeId Filter by person-office ID</parameter>
     * @param string|null $filterOfficeId Filter by office ID (UUID)
     * @param string|null $filterPersonId Filter by person ID (UUID)
     * @param string|null $filterProjectId Filter by project ID (UUID)
     * @param string|null $filterPersonCode Filter by person code
     * @param string|null $filterPersonFirstName Filter by person first name
     * @param string|null $filterPersonLastName Filter by person last name
     * @param bool|null $filterPersonIsEmployee Filter by employee status
     * @param bool|null $filterPersonIsExternal Filter by external status
     * @param string|null $filterPersonTaxCode Filter by person tax code
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterOfficeName Filter by office name
     * @param string|null $filterOfficeCode Filter by office code
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field (e.g., "person_code", "-person_last_name" for reverse)
     * @param bool $history Include historicized items (default: false)
     * @return array List of person-office associations with pagination
     */
    #[
        McpTool(
            name: "list_4hse_person_offices",
            description: "Retrieves a paginated list of 4HSE person-office associations (persons assigned to specific offices within projects) with optional filters. Note: Persons can also exist at project level without office assignment. Requires OAuth2 authentication.",
        ),
    ]
    public function listPersonOffices(
        #[
            Schema(type: "string", description: "Filter by person-office ID"),
        ]
        ?string $filterPersonOfficeId = null,

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
                description: "Filter by person ID (UUID format)",
            ),
        ]
        ?string $filterPersonId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by project ID (UUID format)",
            ),
        ]
        ?string $filterProjectId = null,

        #[
            Schema(type: "string", description: "Filter by person code"),
        ]
        ?string $filterPersonCode = null,

        #[
            Schema(type: "string", description: "Filter by person first name"),
        ]
        ?string $filterPersonFirstName = null,

        #[
            Schema(type: "string", description: "Filter by person last name"),
        ]
        ?string $filterPersonLastName = null,

        #[
            Schema(type: "boolean", description: "Filter by employee status"),
        ]
        ?bool $filterPersonIsEmployee = null,

        #[
            Schema(type: "boolean", description: "Filter by external status"),
        ]
        ?bool $filterPersonIsExternal = null,

        #[
            Schema(type: "string", description: "Filter by person tax code"),
        ]
        ?string $filterPersonTaxCode = null,

        #[
            Schema(type: "string", description: "Filter by project name"),
        ]
        ?string $filterProjectName = null,

        #[
            Schema(type: "string", description: "Filter by office name"),
        ]
        ?string $filterOfficeName = null,

        #[
            Schema(type: "string", description: "Filter by office code"),
        ]
        ?string $filterOfficeCode = null,

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
                description: 'Sort by field (e.g., "person_code", "-person_last_name" for reverse order)',
                enum: [
                    "person_code",
                    "-person_code",
                    "person_first_name",
                    "-person_first_name",
                    "person_last_name",
                    "-person_last_name",
                    "person_birth_date",
                    "-person_birth_date",
                    "person_tax_code",
                    "-person_tax_code",
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
            if ($filterPersonOfficeId !== null) {
                $filter["person_office_id"] = $filterPersonOfficeId;
            }
            if ($filterOfficeId !== null) {
                $filter["office_id"] = $filterOfficeId;
            }
            if ($filterPersonId !== null) {
                $filter["person_id"] = $filterPersonId;
            }
            if ($filterProjectId !== null) {
                $filter["project_id"] = $filterProjectId;
            }
            if ($filterPersonCode !== null) {
                $filter["person_code"] = $filterPersonCode;
            }
            if ($filterPersonFirstName !== null) {
                $filter["person_first_name"] = $filterPersonFirstName;
            }
            if ($filterPersonLastName !== null) {
                $filter["person_last_name"] = $filterPersonLastName;
            }
            if ($filterPersonIsEmployee !== null) {
                $filter["person_is_employee"] = $filterPersonIsEmployee;
            }
            if ($filterPersonIsExternal !== null) {
                $filter["person_is_external"] = $filterPersonIsExternal;
            }
            if ($filterPersonTaxCode !== null) {
                $filter["person_tax_code"] = $filterPersonTaxCode;
            }
            if ($filterProjectName !== null) {
                $filter["project_name"] = $filterProjectName;
            }
            if ($filterOfficeName !== null) {
                $filter["office_name"] = $filterOfficeName;
            }
            if ($filterOfficeCode !== null) {
                $filter["office_code"] = $filterOfficeCode;
            }

            if (!empty($filter)) {
                $params["filter"] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params["sort"] = $sort;
            }

            // Fetch person-offices from 4HSE API
            $result = $client->index("person-office", $params);

            return [
                "success" => true,
                "person_offices" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve person-office associations",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
