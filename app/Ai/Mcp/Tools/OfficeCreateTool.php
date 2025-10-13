<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE office
 */
class OfficeCreateTool
{
    /**
     * Create a new office in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $projectId Project ID (UUID)
     * @param string $name Office name
     * @param string $officeTypeIcon Office type icon
     * @param string|null $description Office description
     * @param string|null $street Street address
     * @param string|null $postalCode Postal code
     * @param string|null $region Region
     * @param string|null $locality Locality/city
     * @param string|null $country Country
     * @param string|null $taxCode Tax code
     * @param string|null $vat VAT number
     * @param string|null $officeTypeId Office type ID (UUID)
     * @param string|null $code Office code
     * @return array Created office details
     */
    #[
        McpTool(
            name: "create_4hse_office",
            description: "Creates a new office/location in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function createOffice(
        #[
            Schema(
                type: "string",
                description: "Project ID in UUID format (required)",
            ),
        ]
        string $projectId,

        #[
            Schema(type: "string", description: "Office name (required)"),
        ]
        string $name,

        #[
            Schema(
                type: "string",
                description: "Office type icon (required)",
                enum: ["office_default", "construction_site"],
            ),
        ]
        string $officeTypeIcon,

        #[
            Schema(type: "string", description: "Office description"),
        ]
        ?string $description = null,

        #[
            Schema(type: "string", description: "Street address"),
        ]
        ?string $street = null,

        #[
            Schema(type: "string", description: "Postal code"),
        ]
        ?string $postalCode = null,

        #[Schema(type: "string", description: "Region")] ?string $region = null,

        #[
            Schema(type: "string", description: "Locality/city"),
        ]
        ?string $locality = null,

        #[
            Schema(type: "string", description: "Country"),
        ]
        ?string $country = null,

        #[
            Schema(type: "string", description: "Tax code"),
        ]
        ?string $taxCode = null,

        #[
            Schema(type: "string", description: "VAT number"),
        ]
        ?string $vat = null,

        #[
            Schema(
                type: "string",
                description: "Office type ID in UUID format",
            ),
        ]
        ?string $officeTypeId = null,

        #[
            Schema(type: "string", description: "Office code"),
        ]
        ?string $code = null,
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

            // Build office data
            $officeData = [
                "project_id" => $projectId,
                "name" => $name,
                "office_type_icon" => $officeTypeIcon,
                "created_at" => time(),
                "updated_at" => time(),
            ];

            // Add optional fields if provided
            if ($description !== null) {
                $officeData["description"] = $description;
            }
            if ($street !== null) {
                $officeData["street"] = $street;
            }
            if ($postalCode !== null) {
                $officeData["postal_code"] = $postalCode;
            }
            if ($region !== null) {
                $officeData["region"] = $region;
            }
            if ($locality !== null) {
                $officeData["locality"] = $locality;
            }
            if ($country !== null) {
                $officeData["country"] = $country;
            }
            if ($taxCode !== null) {
                $officeData["tax_code"] = $taxCode;
            }
            if ($vat !== null) {
                $officeData["vat"] = $vat;
            }
            if ($officeTypeId !== null) {
                $officeData["office_type_id"] = $officeTypeId;
            }
            if ($code !== null) {
                $officeData["code"] = $code;
            }

            // Create office via 4HSE API
            $office = $client->create("office", $officeData);

            return [
                "success" => true,
                "office" => $office,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create office",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
