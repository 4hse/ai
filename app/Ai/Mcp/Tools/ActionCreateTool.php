<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE action
 */
class ActionCreateTool
{
    /**
     * Create a new action in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $actionType Action type (TRAINING, MAINTENANCE, HEALTH, CHECK, PER)
     * @param string $name Action name
     * @param string $subtenantId Subtenant ID (UUID)
     * @param string $tenantId Tenant ID (UUID)
     * @param string|null $code Action code
     * @param string|null $description Action description
     * @param string|null $validityUnit Validity unit (YEAR, MONTH, DAY)
     * @param int|null $validity Validity period
     * @param int|null $expireInterval Expiration interval
     * @param string|null $manager Manager (JSON format)
     * @param string|null $assignee Assignee (JSON format)
     * @param string|null $watcher Watcher (JSON format)
     * @param string|null $data Additional data (JSON format)
     * @return array Created action details
     */
    #[McpTool(
        name: 'create_4hse_action',
        description: 'Creates a new action in 4HSE. Requires OAuth2 authentication.'
    )]
    public function createAction(
        #[Schema(
            type: 'string',
            description: 'Action type (required)',
            enum: ['TRAINING', 'MAINTENANCE', 'HEALTH', 'CHECK', 'PER']
        )]
        string $actionType,

        #[Schema(
            type: 'string',
            description: 'Action name (required)'
        )]
        string $name,

        #[Schema(
            type: 'string',
            description: 'Subtenant ID in UUID format (required)'
        )]
        string $subtenantId,

        #[Schema(
            type: 'string',
            description: 'Tenant ID in UUID format (required)'
        )]
        string $tenantId,

        #[Schema(
            type: 'string',
            description: 'Action code'
        )]
        ?string $code = null,

        #[Schema(
            type: 'string',
            description: 'Action description'
        )]
        ?string $description = null,

        #[Schema(
            type: 'string',
            description: 'Validity unit',
            enum: ['YEAR', 'MONTH', 'DAY']
        )]
        ?string $validityUnit = null,

        #[Schema(
            type: 'integer',
            description: 'Validity period'
        )]
        ?int $validity = null,

        #[Schema(
            type: 'integer',
            description: 'Expiration interval'
        )]
        ?int $expireInterval = null,

        #[Schema(
            type: 'string',
            description: 'Manager (JSON format)'
        )]
        ?string $manager = null,

        #[Schema(
            type: 'string',
            description: 'Assignee (JSON format)'
        )]
        ?string $assignee = null,

        #[Schema(
            type: 'string',
            description: 'Watcher (JSON format)'
        )]
        ?string $watcher = null,

        #[Schema(
            type: 'string',
            description: 'Additional data (JSON format)'
        )]
        ?string $data = null
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

            // Build action data
            $actionData = [
                'action_type' => $actionType,
                'name' => $name,
                'subtenant_id' => $subtenantId,
                'tenant_id' => $tenantId,
            ];

            // Add optional fields if provided
            if ($code !== null) {
                $actionData['code'] = $code;
            }
            if ($description !== null) {
                $actionData['description'] = $description;
            }
            if ($validityUnit !== null) {
                $actionData['validity_unit'] = $validityUnit;
            }
            if ($validity !== null) {
                $actionData['validity'] = $validity;
            }
            if ($expireInterval !== null) {
                $actionData['expire_interval'] = $expireInterval;
            }
            if ($manager !== null) {
                $actionData['manager'] = $manager;
            }
            if ($assignee !== null) {
                $actionData['assignee'] = $assignee;
            }
            if ($watcher !== null) {
                $actionData['watcher'] = $watcher;
            }
            if ($data !== null) {
                $actionData['data'] = $data;
            }

            // Create action via 4HSE API
            $action = $client->create('action', $actionData);

            return [
                'success' => true,
                'action' => $action,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to create action',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
