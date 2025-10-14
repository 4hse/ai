<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE action subscription (a "need" - assignment of requirement to person/resource)
 */
class ActionSubscriptionViewTool
{
    /**
     * Get a single action subscription by ID.
     * Action subscriptions represent the "need" - they link a person or resource to an action requirement.
     * They create requirements that must later be satisfied by certificates.
     * Requires OAuth2 authentication.
     *
     * @param string $id Action subscription ID (UUID) - the specific need/assignment to retrieve
     * @return array Action subscription details
     */
    #[
        McpTool(
            name: "view_4hse_action_subscription",
            description: "Retrieves a single 4HSE action subscription by ID. Action subscriptions represent the 'need' - the assignment of a training course, maintenance plan, procedure, etc. to a person or resource. Use this to get detailed information about a specific requirement assignment. Requires OAuth2 authentication.",
        ),
    ]
    public function viewActionSubscription(
        #[
            Schema(
                type: "string",
                description: "Action subscription ID (UUID format) - the ID of the specific need/assignment to retrieve",
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

            // Fetch action subscription from 4HSE API
            $actionSubscription = $client->view("action-subscription", $id);

            return [
                "success" => true,
                "action_subscription" => $actionSubscription,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve action subscription",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
