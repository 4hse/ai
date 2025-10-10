<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE office
 */
class OfficeViewTool
{
    /**
     * Get a single office by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Office ID (UUID)
     * @return array Office details
     */
    #[McpTool(
        name: 'view_4hse_office',
        description: 'Retrieves a single 4HSE office by ID. View complete office details including address, project, type, tax information. Requires OAuth2 authentication.'
    )]
    public function viewOffice(
        #[Schema(
            type: 'string',
            description: 'Office ID (UUID format)'
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

            // Fetch office from 4HSE API
            $office = $client->view('office', $id);

            return [
                'success' => true,
                'office' => $office,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve office',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
