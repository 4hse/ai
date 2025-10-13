<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE work group person
 */
class WorkGroupPersonViewTool
{
    /**
     * Get a single work group person by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Work group person ID (UUID)
     * @return array Work group person details
     */
    #[McpTool(
        name: 'view_4hse_work_group_person',
        description: 'Retrieves a single 4HSE work group person by ID. View complete work group person details including association between work groups and people. Requires OAuth2 authentication.'
    )]
    public function viewWorkGroupPerson(
        #[Schema(
            type: 'string',
            description: 'Work group person ID (UUID format)'
        )]
        string $id
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

            // Fetch work group person from 4HSE API
            $workGroupPerson = $client->view('work-group-person', $id);

            return [
                'success' => true,
                'work_group_person' => $workGroupPerson,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve work group person',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
