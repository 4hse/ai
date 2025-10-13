<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE certificate action
 */
class CertificateActionViewTool
{
    /**
     * Get a single certificate action by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Certificate action ID (UUID)
     * @return array Certificate action details
     */
    #[
        McpTool(
            name: "view_4hse_certificate_action",
            description: "Retrieves a single 4HSE certificate-action association by ID. View complete details including action name, certificate name, expiration dates, resource type, office. Requires OAuth2 authentication.",
        ),
    ]
    public function viewCertificateAction(
        #[
            Schema(
                type: "string",
                description: "Certificate action ID (UUID format)",
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

            // Fetch certificate action from 4HSE API
            $certificateAction = $client->view("certificate-action", $id);

            return [
                "success" => true,
                "certificate_action" => $certificateAction,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve certificate action",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
