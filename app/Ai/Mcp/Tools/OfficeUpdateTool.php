<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE office
 */
class OfficeUpdateTool
{
    /**
     * Update an existing office in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Office ID (UUID)
     * @param string|null $projectId Project ID (UUID)
     * @param string|null $name Office name
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
     * @param string|null $officeTypeIcon Office type icon
     * @return array Updated office details
     */
    #[
        McpTool(
            name: "update_4hse_office",
            description: "Updates an existing office in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateOffice(
        #[
            Schema(
                type: "string",
                description: "Office ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Project ID in UUID format"),
        ]
        ?string $projectId = null,

        #[
            Schema(type: "string", description: "Office name"),
        ]
        ?string $name = null,

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

        #[
            Schema(
                type: "string",
                description: "Office type icon",
                enum: ["office_default", "construction_site"],
            ),
        ]
        ?string $officeTypeIcon = null,
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

            // Build office data with only provided fields
            $officeData = [
                "updated_at" => time(),
            ];

            if ($projectId !== null) {
                $officeData["project_id"] = $projectId;
            }
            if ($name !== null) {
                $officeData["name"] = $name;
            }
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
            if ($officeTypeIcon !== null) {
                $officeData["office_type_icon"] = $officeTypeIcon;
            }

            // Update office via 4HSE API
            $office = $client->update("office", $id, $officeData);

            return [
                "success" => true,
                "office" => $office,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update office",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
