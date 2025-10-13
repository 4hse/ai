<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for deleting a 4HSE person-office association.
 * This removes the assignment of a person to a specific office within a project.
 * Note: Persons can exist at the project level without being assigned to specific offices.
 */
class PersonOfficeDeleteTool
{
    /**
     * Delete a person-office association from 4HSE.
     * This removes the assignment of a person to a specific office within a project.
     * Note: Persons can exist at the project level without being assigned to offices.
     * Requires OAuth2 authentication.
     *
     * @param string $id Person-office association ID (UUID)
     * @param bool $force Force deletion of the entity and all related entities.
     * @return array Deletion result
     */
    #[
        McpTool(
            name: "delete_4hse_person_office",
            description: "Deletes a person-office association in 4HSE, removing the assignment of a person to a specific office within a project. Note: Persons can also exist at project level without office assignment. Requires OAuth2 authentication.",
        ),
    ]
    public function deletePersonOffice(
        #[
            Schema(
                type: "string",
                description: "Person-office association ID (UUID format)",
            ),
        ]
        string $id,

        #[
            Schema(
                type: "boolean",
                description: "Force deletion of the entity and all related entities",
            ),
        ]
        bool $force = true,
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

            // Build query parameters
            $queryParams = [];
            if ($force) {
                $queryParams["force"] = "true";
            }

            // Delete person-office via 4HSE API
            $result = $client->delete("person-office", $id, $queryParams);

            return [
                "success" => true,
                "message" => "Person-office association deleted successfully",
                "deleted" => $result,
            ];
        } catch (Throwable $e) {
            // Check if this is a 400 error with related entities info
            if ($e->getCode() === 400) {
                return [
                    "error" => "Cannot delete person-office association",
                    "message" => $e->getMessage(),
                    "code" => $e->getCode(),
                    "hint" =>
                        "The person-office has related entities. Use force=true to delete all related entities.",
                ];
            }

            return [
                "error" => "Failed to delete person-office association",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
