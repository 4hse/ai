<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE action subscription
 */
class ActionSubscriptionUpdateTool
{
    /**
     * Update an existing action subscription in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Action subscription ID (UUID)
     * @param string|null $actionId Action ID (UUID)
     * @param string|null $subscriberId Subscriber ID (UUID)
     * @param string|null $subscriberType Subscriber type
     * @param string|null $subtenantId Subtenant ID (UUID)
     * @param string|null $tenantId Tenant ID (UUID)
     * @param array|null $data Additional data (JSON object)
     * @return array Updated action subscription details
     */
    #[McpTool(
        name: 'update_4hse_action_subscription',
        description: 'Updates an existing action subscription in 4HSE. Requires OAuth2 authentication.'
    )]
    public function updateActionSubscription(
        #[Schema(
            type: 'string',
            description: 'Action subscription ID in UUID format (required)'
        )]
        string $id,

        #[Schema(
            type: 'string',
            description: 'Action ID in UUID format'
        )]
        ?string $actionId = null,

        #[Schema(
            type: 'string',
            description: 'Subscriber ID in UUID format'
        )]
        ?string $subscriberId = null,

        #[Schema(
            type: 'string',
            description: 'Subscriber type',
            enum: ['PERSON', 'MATERIAL_ITEM', 'ROLE', 'WORK_GROUP', 'WORK_ENVIRONMENT', 'SUBSTANCE', 'EQUIPMENT']
        )]
        ?string $subscriberType = null,

        #[Schema(
            type: 'string',
            description: 'Subtenant ID in UUID format'
        )]
        ?string $subtenantId = null,

        #[Schema(
            type: 'string',
            description: 'Tenant ID in UUID format'
        )]
        ?string $tenantId = null,

        #[Schema(
            type: 'object',
            description: 'Additional data (JSON object)'
        )]
        ?array $data = null
    ): array {
        try {
            // Get bearer token from app container (set by MCP middleware)
            $bearerToken = app()->has('mcp.bearer_token') ? app('mcp.bearer_token') : null;

            if (!$bearerToken) {
                return [
                    'error' => 'Authentication required',
                    'message' => 'This tool requires OAuth2 authentication. The bearer token was not found in the request context.',
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build action subscription data with only provided fields
            $subscriptionData = [];

            if ($actionId !== null) {
                $subscriptionData['action_id'] = $actionId;
            }
            if ($subscriberId !== null) {
                $subscriptionData['subscriber_id'] = $subscriberId;
            }
            if ($subscriberType !== null) {
                $subscriptionData['subscriber_type'] = $subscriberType;
            }
            if ($subtenantId !== null) {
                $subscriptionData['subtenant_id'] = $subtenantId;
            }
            if ($tenantId !== null) {
                $subscriptionData['tenant_id'] = $tenantId;
            }
            if ($data !== null) {
                $subscriptionData['data'] = $data;
            }

            // Update action subscription via 4HSE API
            $actionSubscription = $client->update('action-subscription', $id, $subscriptionData);

            return [
                'success' => true,
                'action_subscription' => $actionSubscription,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to update action subscription',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
