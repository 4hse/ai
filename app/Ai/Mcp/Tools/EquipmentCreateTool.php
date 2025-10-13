<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE equipment
 */
class EquipmentCreateTool
{
    /**
     * Create new equipment in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $officeId Office ID (UUID)
     * @param string $name Equipment name
     * @param string|null $code Equipment code
     * @param string|null $description Equipment description
     * @param string|null $projectId Project ID (UUID)
     * @param string|null $serial Equipment serial number
     * @param string|null $vendor Equipment vendor
     * @param string|null $model Equipment model
     * @return array Created equipment details
     */
    #[
        McpTool(
            name: "create_4hse_equipment",
            description: "Creates a new equipment in 4HSE. Requires OAuth2 authentication.",
        ),
    ]
    public function createEquipment(
        #[
            Schema(
                type: "string",
                description: "Office ID in UUID format (required)",
            ),
        ]
        string $officeId,

        #[
            Schema(type: "string", description: "Equipment name (required)"),
        ]
        string $name,

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

            // Build equipment data
            $equipmentData = [
                "office_id" => $officeId,
                "name" => $name,
            ];

            // Add optional fields if provided
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

            // Create equipment via 4HSE API
            $equipment = $client->create("equipment", $equipmentData);

            return [
                "success" => true,
                "equipment" => $equipment,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create equipment",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
