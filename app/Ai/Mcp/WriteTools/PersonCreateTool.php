<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE person
 */
class PersonCreateTool
{
    /**
     * Create a new person in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $firstName Person's first name (max 70 chars)
     * @param string $lastName Person's last name (max 70 chars)
     * @param string $birthDate Birth date (format: YYYY-MM-DD)
     * @param string $projectId Project ID (UUID)
     * @param string|null $entityId Entity ID
     * @param string|null $relatedUser Related user identifier
     * @param string|null $code Person code (max 50 chars)
     * @param string|null $street Street address (max 255 chars)
     * @param string|null $locality Locality (max 50 chars)
     * @param string|null $postalCode Postal code (max 50 chars)
     * @param string|null $region Region (max 10 chars)
     * @param string|null $sex Sex (max 10 chars)
     * @param string|null $country Country (max 50 chars)
     * @param string|null $birthPlace Birth place (max 150 chars)
     * @param string|null $taxCode Tax code (max 30 chars)
     * @param string|null $note Additional notes
     * @param string|null $contractType Contract type
     * @param int|null $isEmployee Is employee (0 or 1)
     * @param int|null $isPreventionPeople Is prevention people (0 or 1)
     * @param bool $uniqueCode If true and person with same code exists, update instead of create
     * @return array Created person details
     */
    #[
        McpTool(
            name: "create_4hse_person",
            description: "Creates a new person in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function createPerson(
        #[
            Schema(
                type: "string",
                description: "Person's first name (required, max 70 characters)",
            ),
        ]
        string $firstName,

        #[
            Schema(
                type: "string",
                description: "Person's last name (required, max 70 characters)",
            ),
        ]
        string $lastName,

        #[
            Schema(
                type: "string",
                description: "Birth date in YYYY-MM-DD format (required)",
            ),
        ]
        string $birthDate,

        #[
            Schema(
                type: "string",
                description: "Project ID in UUID format (required)",
            ),
        ]
        string $projectId,

        #[
            Schema(type: "string", description: "Entity ID"),
        ]
        ?string $entityId = null,

        #[
            Schema(type: "string", description: "Related user identifier"),
        ]
        ?string $relatedUser = null,

        #[
            Schema(
                type: "string",
                description: "Person code (max 50 characters)",
            ),
        ]
        ?string $code = null,

        #[
            Schema(
                type: "string",
                description: "Street address (max 255 characters)",
            ),
        ]
        ?string $street = null,

        #[
            Schema(type: "string", description: "Locality (max 50 characters)"),
        ]
        ?string $locality = null,

        #[
            Schema(
                type: "string",
                description: "Postal code (max 50 characters)",
            ),
        ]
        ?string $postalCode = null,

        #[
            Schema(type: "string", description: "Region (max 10 characters)"),
        ]
        ?string $region = null,

        #[
            Schema(type: "string", description: "Sex (max 10 characters)"),
        ]
        ?string $sex = null,

        #[
            Schema(type: "string", description: "Country (max 50 characters)"),
        ]
        ?string $country = null,

        #[
            Schema(
                type: "string",
                description: "Birth place (max 150 characters)",
            ),
        ]
        ?string $birthPlace = null,

        #[
            Schema(type: "string", description: "Tax code (max 30 characters)"),
        ]
        ?string $taxCode = null,

        #[
            Schema(type: "string", description: "Additional notes"),
        ]
        ?string $note = null,

        #[
            Schema(type: "string", description: "Contract type"),
        ]
        ?string $contractType = null,

        #[
            Schema(
                type: "integer",
                description: "Is employee (0 or 1)",
                enum: [0, 1],
            ),
        ]
        ?int $isEmployee = null,

        #[
            Schema(
                type: "integer",
                description: "Is prevention people (0 or 1)",
                enum: [0, 1],
            ),
        ]
        ?int $isPreventionPeople = null,

        #[
            Schema(
                type: "boolean",
                description: "If true and person with same code exists, update instead of create",
            ),
        ]
        bool $uniqueCode = false,
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

            // Build person data
            $data = [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "birth_date" => $birthDate,
                "project_id" => $projectId,
                "unique_code" => $uniqueCode,
                // Default values
                "is_employee" => $isEmployee !== null ? $isEmployee : 1,
                "is_prevention_people" =>
                    $isPreventionPeople !== null ? $isPreventionPeople : 0,
            ];

            // Add optional fields if provided
            if ($entityId !== null) {
                $data["entity_id"] = $entityId;
            }
            if ($relatedUser !== null) {
                $data["related_user"] = $relatedUser;
            }
            if ($code !== null) {
                $data["code"] = $code;
            }
            if ($street !== null) {
                $data["street"] = $street;
            }
            if ($locality !== null) {
                $data["locality"] = $locality;
            }
            if ($postalCode !== null) {
                $data["postal_code"] = $postalCode;
            }
            if ($region !== null) {
                $data["region"] = $region;
            }
            if ($sex !== null) {
                $data["sex"] = $sex;
            }
            if ($country !== null) {
                $data["country"] = $country;
            }
            if ($birthPlace !== null) {
                $data["birth_place"] = $birthPlace;
            }
            if ($taxCode !== null) {
                $data["tax_code"] = $taxCode;
            }
            if ($note !== null) {
                $data["note"] = $note;
            }
            if ($contractType !== null) {
                $data["contract_type"] = $contractType;
            }

            // Create person via 4HSE API
            $person = $client->create("person", $data);

            return [
                "success" => true,
                "person" => $person,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create person",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
