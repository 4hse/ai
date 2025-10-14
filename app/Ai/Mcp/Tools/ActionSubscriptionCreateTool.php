<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE action subscription
 */
class ActionSubscriptionCreateTool
{
    /**
     * Create a new action subscription in 4HSE.
     * Action subscriptions represent the "need" - they link a person or material resource to an action requirement.
     * This creates the requirement that must later be satisfied by a certificate.
     * Requires OAuth2 authentication.
     *
     * @param string $actionId Action ID (UUID) - the training course, maintenance plan, etc. that is required
     * @param string $subscriberId Subscriber ID (UUID) - the person or resource that needs this action
     * @param string $subscriberType Subscriber type - what type of resource needs this action
     * @param string $subtenantId Subtenant ID (UUID)
     * @param string $tenantId Tenant ID (UUID)
     * @param array|null $data Additional data (JSON object)
     * @return array Created action subscription details
     */
    #[
        McpTool(
            name: "create_4hse_action_subscription",
            description: "Creates a new action subscription in 4HSE. This represents a 'need' - linking a person or material resource to an action requirement (like assigning a training course to a person). The subscription creates the requirement that must later be satisfied by a certificate. Requires OAuth2 authentication.",
        ),
    ]
    public function createActionSubscription(
        #[
            Schema(
                type: "string",
                description: "Action ID in UUID format (required) - the training course, maintenance plan, procedure, etc. that is required",
            ),
        ]
        string $actionId,

        #[
            Schema(
                type: "string",
                description: "Subscriber ID in UUID format (required) - the person, equipment, or other resource that needs this action",
            ),
        ]
        string $subscriberId,

        #[
            Schema(
                type: "string",
                description: "Subscriber type (required) - what type of resource needs this action",
                enum: [
                    "PERSON",
                    "MATERIAL_ITEM",
                    "ROLE",
                    "WORK_GROUP",
                    "WORK_ENVIRONMENT",
                    "SUBSTANCE",
                    "EQUIPMENT",
                ],
            ),
        ]
        string $subscriberType,

        #[
            Schema(
                type: "string",
                description: "Subtenant ID in UUID format (required)",
            ),
        ]
        string $subtenantId,

        #[
            Schema(
                type: "string",
                description: "Tenant ID in UUID format (required)",
            ),
        ]
        string $tenantId,

        #[
            Schema(
                type: "object",
                description: "Additional data (JSON object)",
            ),
        ]
        ?array $data = null,
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

            // Build action subscription data
            $subscriptionData = [
                "action_id" => $actionId,
                "subscriber_id" => $subscriberId,
                "subscriber_type" => $subscriberType,
                "subtenant_id" => $subtenantId,
                "tenant_id" => $tenantId,
            ];

            // Add optional fields if provided
            if ($data !== null) {
                $subscriptionData["data"] = $data;
            }

            // Create action subscription via 4HSE API
            $actionSubscription = $client->create(
                "action-subscription",
                $subscriptionData,
            );

            return [
                "success" => true,
                "action_subscription" => $actionSubscription,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to create action subscription",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
