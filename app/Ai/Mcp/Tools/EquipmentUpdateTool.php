<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE equipment
 */
class EquipmentUpdateTool
{
    /**
     * Update existing equipment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Equipment ID (UUID)
     * @param string|null $officeId Office ID (UUID)
     * @param string|null $name Equipment name
     * @param string|null $code Equipment code
     * @param string|null $description Equipment description
     * @param string|null $projectId Project ID (UUID)
     * @param string|null $serial Equipment serial number
     * @param string|null $vendor Equipment vendor
     * @param string|null $model Equipment model
     * @return array Updated equipment details
     */
    #[
        McpTool(
            name: "update_4hse_equipment",
            description: "Updates an existing equipment in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function updateEquipment(
        #[
            Schema(
                type: "string",
                description: "Equipment ID in UUID format (required)",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Office ID in UUID format"),
        ]
        ?string $officeId = null,

        #[
            Schema(type: "string", description: "Equipment name"),
        ]
        ?string $name = null,

        #[
            Schema(type: "string", description: "Equipment code"),
        ]
        ?string $code = null,

        #[
            Schema(type: "string", description: "Equipment description"),
        ]
        ?string $description = null,

        #[
            Schema(type: "string", description: "Project ID in UUID format"),
        ]
        ?string $projectId = null,

        #[
            Schema(type: "string", description: "Equipment serial number"),
        ]
        ?string $serial = null,

        #[
            Schema(type: "string", description: "Equipment vendor"),
        ]
        ?string $vendor = null,

        #[
            Schema(type: "string", description: "Equipment model"),
        ]
        ?string $model = null,
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

            // Build equipment data with only provided fields
            $equipmentData = [];

            if ($officeId !== null) {
                $equipmentData["office_id"] = $officeId;
            }
            if ($name !== null) {
                $equipmentData["name"] = $name;
            }
            if ($code !== null) {
                $equipmentData["code"] = $code;
            }
            if ($description !== null) {
                $equipmentData["description"] = $description;
            }
            if ($projectId !== null) {
                $equipmentData["project_id"] = $projectId;
            }
            if ($serial !== null) {
                $equipmentData["serial"] = $serial;
            }
            if ($vendor !== null) {
                $equipmentData["vendor"] = $vendor;
            }
            if ($model !== null) {
                $equipmentData["model"] = $model;
            }

            // Update equipment via 4HSE API
            $equipment = $client->update("equipment", $id, $equipmentData);

            return [
                "success" => true,
                "equipment" => $equipment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update equipment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
