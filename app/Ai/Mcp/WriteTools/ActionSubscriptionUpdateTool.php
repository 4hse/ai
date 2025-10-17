<?php

namespace App\Ai\Mcp\WriteTools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE action subscription (a "need" - assignment of requirement to person/resource)
 */
class ActionSubscriptionUpdateTool
{
    /**
     * Update an existing action subscription in 4HSE.
     * Action subscriptions represent the "need" - they link a person or resource to an action requirement.
     * They create requirements that must later be satisfied by certificates.
     * Requires OAuth2 authentication.
     *
     * @param string $id Action subscription ID (UUID) - the specific need/assignment to update
     * @param string|null $actionId Action ID (UUID) - the training course, maintenance plan, procedure, etc.
     * @param string|null $subscriberId Subscriber ID (UUID) - the person or resource that needs the action
     * @param string|null $subscriberType Subscriber type - what type of resource needs the action
     * @param string|null $subtenantId Subtenant ID (UUID)
     * @param string|null $tenantId Tenant ID (UUID)
     * @param array|null $data Additional data (JSON object)
     * @return array Updated action subscription details
     */
    #[
        McpTool(
            name: "update_4hse_action_subscription",
            description: "Updates an existing action subscription in 4HSE. Action subscriptions represent the 'need' - the assignment of a training course, maintenance plan, procedure, etc. to a person or resource. Use this to modify existing requirement assignments. Requires OAuth2 authentication.",
        ),
    ]
    public function updateActionSubscription(
        #[
            Schema(
                type: "string",
                description: "Action subscription ID (UUID format, required) - the ID of the specific need/assignment to update",
            ),
        ]
        string $id,

        #[
            Schema(type: "string", description: "Action ID in UUID format"),
        ]
        ?string $actionId = null,

        #[
            Schema(type: "string", description: "Subscriber ID in UUID format"),
        ]
        ?string $subscriberId = null,

        #[
            Schema(
                type: "string",
                description: "Subscriber type - what type of resource needs the action",
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
        ?string $subscriberType = null,

        #[
            Schema(type: "string", description: "Subtenant ID in UUID format"),
        ]
        ?string $subtenantId = null,

        #[
            Schema(type: "string", description: "Tenant ID in UUID format"),
        ]
        ?string $tenantId = null,

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

            // Build action subscription data with only provided fields
            $subscriptionData = [];

            if ($actionId !== null) {
                $subscriptionData["action_id"] = $actionId;
            }
            if ($subscriberId !== null) {
                $subscriptionData["subscriber_id"] = $subscriberId;
            }
            if ($subscriberType !== null) {
                $subscriptionData["subscriber_type"] = $subscriberType;
            }
            if ($subtenantId !== null) {
                $subscriptionData["subtenant_id"] = $subtenantId;
            }
            if ($tenantId !== null) {
                $subscriptionData["tenant_id"] = $tenantId;
            }
            if ($data !== null) {
                $subscriptionData["data"] = $data;
            }

            // Update action subscription via 4HSE API
            $actionSubscription = $client->update(
                "action-subscription",
                $id,
                $subscriptionData,
            );

            return [
                "success" => true,
                "action_subscription" => $actionSubscription,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to update action subscription",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
