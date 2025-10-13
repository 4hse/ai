<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE certificate action
 */
class CertificateActionCreateTool
{
    /**
     * Create a new certificate action in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $certificateId Certificate ID (UUID)
     * @param int $actionId Action ID
     * @param string $tenantId Tenant ID (UUID)
     * @param string|null $dateExpire Expiration date (format: YYYY-MM-DD)
     * @return array Created certificate action details
     */
    #[
        McpTool(
            name: "create_4hse_certificate_action",
            description: "Creates a new certificate-action association in 4HSE. Links a certificate to an action. Requires OAuth2 authentication.",
        ),
    ]
    public function createCertificateAction(
        #[
            Schema(
                type: "string",
                description: "Certificate ID in UUID format (required)",
            ),
        ]
        string $certificateId,

        #[
            Schema(type: "integer", description: "Action ID (required)"),
        ]
        int $actionId,

        #[
            Schema(
                type: "string",
                description: "Tenant ID in UUID format (required)",
            ),
        ]
        string $tenantId,

        #[
            Schema(
                type: "string",
                description: "Expiration date in YYYY-MM-DD format",
            ),
        ]
        ?string $dateExpire = null,
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

            // Build certificate action data
            $certificateActionData = [
                "certificate_id" => $certificateId,
                "action_id" => $actionId,
                "tenant_id" => $tenantId,
            ];

            // Add optional fields if provided
            if ($dateExpire !== null) {
                $certificateActionData["date_expire"] = $dateExpire;
            }

            // Create certificate action via 4HSE API
            $certificateAction = $client->create(
                "certificate-action",
                $certificateActionData,
            );

            return [
                "success" => true,
                "certificate_action" => $certificateAction,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create certificate action",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
