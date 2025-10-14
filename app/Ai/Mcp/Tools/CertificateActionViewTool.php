<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE certificate action (a "link" - association between certificate and specific action)
 */
class CertificateActionViewTool
{
    /**
     * Get a single certificate action by ID.
     * Certificate-action associations link certificates to specific actions, establishing which action requirement the certificate satisfies.
     * This completes the workflow: Action → Action-Subscription (need) → Certificate → Certificate-Action (resolution).
     * Requires OAuth2 authentication.
     *
     * @param string $id Certificate action ID (UUID) - the specific link between certificate and action to retrieve
     * @return array Certificate action details
     */
    #[
        McpTool(
            name: "view_4hse_certificate_action",
            description: "Retrieves a single 4HSE certificate-action association by ID (a 'link' that specifies which action requirement a certificate satisfies). Certificate-action associations complete the workflow by linking certificates to specific training courses, maintenance plans, procedures, etc. View complete details including action name, certificate name, expiration dates, resource type, office. Requires OAuth2 authentication.",
        ),
    ]
    public function viewCertificateAction(
        #[
            Schema(
                type: "string",
                description: "Certificate action ID (UUID format) - the ID of the specific link between certificate and action to retrieve",
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
