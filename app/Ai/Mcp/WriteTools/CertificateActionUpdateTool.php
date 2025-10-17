<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE certificate action (a "link" - association between certificate and specific action)
 */
class CertificateActionUpdateTool
{
    /**
     * Update an existing certificate action in 4HSE.
     * Certificate-action associations link certificates to specific actions, establishing which action requirement the certificate satisfies.
     * This completes the workflow: Action → Action-Subscription (need) → Certificate → Certificate-Action (resolution).
     * Requires OAuth2 authentication.
     *
     * @param string $id Certificate action ID (UUID)
     * @param string|null $certificateId Certificate ID (UUID)
     * @param int|null $actionId Action ID
     * @param string|null $dateExpire Expiration date (format: YYYY-MM-DD)
     * @param string|null $tenantId Tenant ID (UUID)
     * @return array Updated certificate action details
     */
    #[
        McpTool(
            name: "update_4hse_certificate_action",
            description: "Updates an existing certificate-action association in 4HSE (a 'link' that specifies which action requirement a certificate satisfies). Certificate-action associations complete the workflow by linking certificates to specific training courses, maintenance plans, procedures, etc. Use this to modify existing certificate-action links or update their expiration dates. Requires OAuth2 authentication.",
        ),
    ]
    public function updateCertificateAction(
        #[
            Schema(
                type: "string",
                description: "Certificate action ID (UUID format, required) - the ID of the specific link between certificate and action to update",
            ),
        ]
        string $id,

        #[
            Schema(
                type: "string",
                description: "Certificate ID in UUID format",
            ),
        ]
        ?string $certificateId = null,

        #[
            Schema(type: "integer", description: "Action ID"),
        ]
        ?int $actionId = null,

        #[
            Schema(
                type: "string",
                description: "Expiration date in YYYY-MM-DD format - when this certificate-action coverage expires (may differ from certificate expiration)",
            ),
        ]
        ?string $dateExpire = null,

        #[
            Schema(type: "string", description: "Tenant ID in UUID format"),
        ]
        ?string $tenantId = null,
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

            // Build certificate action data with only provided fields
            $certificateActionData = [];

            if ($certificateId !== null) {
                $certificateActionData["certificate_id"] = $certificateId;
            }
            if ($actionId !== null) {
                $certificateActionData["action_id"] = $actionId;
            }
            if ($dateExpire !== null) {
                $certificateActionData["date_expire"] = $dateExpire;
            }
            if ($tenantId !== null) {
                $certificateActionData["tenant_id"] = $tenantId;
            }

            // Update certificate action via 4HSE API
            $certificateAction = $client->update(
                "certificate-action",
                $id,
                $certificateActionData,
            );

            return [
                "success" => true,
                "certificate_action" => $certificateAction,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update certificate action",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
