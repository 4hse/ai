<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tool for historicizing a 4HSE person
 */
class PersonHistoricizeTool
{
    /**
     * Historicize a person in 4HSE by ID or by code+project_id.
     * Requires OAuth2 authentication.
     *
     * @param string|null $id Person ID (UUID). Required if code and projectId are not provided.
     * @param string|null $code Person code. Required together with projectId if id is not provided.
     * @param string|null $projectId Project ID (UUID). Required together with code if id is not provided.
     * @param string|null $date Date of historicization (format: YYYY-MM-DD HH:MM:SS). If not provided, current date will be used.
     * @return array Historicization result
     */
    #[
        McpTool(
            name: "historicize_4hse_person",
            description: "Historicizes a person in 4HSE by ID or by code+project_id. Requires OAuth2 authentication.",
        ),
    ]
    public function historicizePerson(
        #[
            Schema(
                type: "string",
                description: "Person ID (UUID format). Required if code and projectId are not provided.",
            ),
        ]
        ?string $id = null,

        #[
            Schema(
                type: "string",
                description: "Person code. Required together with projectId if id is not provided.",
            ),
        ]
        ?string $code = null,

        #[
            Schema(
                type: "string",
                description: "Project ID (UUID format). Required together with code if id is not provided.",
            ),
        ]
        ?string $projectId = null,

        #[
            Schema(
                type: "string",
                description: "Date of historicization (format: YYYY-MM-DD HH:MM:SS). If not provided, current date will be used.",
            ),
        ]
        ?string $date = null,
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

            // Validate parameters
            if (!$id && (!$code || !$projectId)) {
                return [
                    "error" => "Invalid parameters",
                    "message" =>
                        "Either id must be provided, or both code and projectId must be provided.",
                ];
            }

            // Build base URL
            $baseUrl = config("fourhse.api.base_url");
            $timeout = config("fourhse.api.timeout");
            $verifySSL = config("fourhse.api.verify_ssl");

            // Build query parameters for alternative lookup
            $queryParams = [];
            if (!$id) {
                $queryParams["code"] = $code;
                $queryParams["project_id"] = $projectId;
            }

            // Build endpoint URL
            $personIdentifier = $id ?? "lookup";
            $endpoint = "/v2/person/historicize/{$personIdentifier}";
            if (!empty($queryParams)) {
                $endpoint .= "?" . http_build_query($queryParams);
            }

            // Build request body
            $body = [];
            if ($date !== null) {
                $body["date"] = $date;
            }

            Log::info("Historicizing person via 4HSE API", [
                "endpoint" => $endpoint,
                "has_date" => $date !== null,
            ]);

            // Make the historicize request
            $http = Http::withToken($bearerToken)
                ->timeout($timeout)
                ->acceptJson()
                ->contentType("application/json");

            if (!$verifySSL) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($baseUrl . $endpoint, $body);

            if (!$response->successful()) {
                Log::error("Failed to historicize person", [
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);

                throw new \Exception(
                    "Failed to historicize person: " . $response->body(),
                    $response->status(),
                );
            }

            Log::info("Person historicized successfully");

            return [
                "success" => true,
                "message" => "Person historicized successfully",
                "person" => $response->json(),
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to historicize person",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
