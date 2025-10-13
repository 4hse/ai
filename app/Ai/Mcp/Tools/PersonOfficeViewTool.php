<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE person-office association
 */
class PersonOfficeViewTool
{
    /**
     * Get a single person-office association by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Person-office association ID (UUID)
     * @return array Person-office association details
     */
    #[
        McpTool(
            name: "view_4hse_person_office",
            description: "Retrieves a single 4HSE person-office association by ID. Requires OAuth2 authentication.",
        ),
    ]
    public function viewPersonOffice(
        #[
            Schema(
                type: "string",
                description: "Person-office association ID (UUID format)",
            ),
        ]
        string $id,
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

            // Fetch person-office from 4HSE API
            $personOffice = $client->view("person-office", $id);

            return [
                "success" => true,
                "person_office" => $personOffice,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve person-office association",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
