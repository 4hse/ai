<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE certificate
 */
class CertificateViewTool
{
    /**
     * Get a single certificate by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Certificate ID (UUID)
     * @return array Certificate details
     */
    #[
        McpTool(
            name: "view_4hse_certificate",
            description: "Retrieves a single 4HSE certificate by ID. View complete certificate details including dates, validity, notes. Requires OAuth2 authentication.",
        ),
    ]
    public function viewCertificate(
        #[
            Schema(type: "string", description: "Certificate ID (UUID format)"),
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

            // Fetch certificate from 4HSE API
            $certificate = $client->view("certificate", $id);

            return [
                "success" => true,
                "certificate" => $certificate,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve certificate",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
