<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for viewing a single 4HSE equipment
 */
class EquipmentViewTool
{
    /**
     * Get single equipment by ID.
     * Requires OAuth2 authentication.
     *
     * @param string $id Equipment ID (UUID)
     * @return array Equipment details
     */
    #[McpTool(
        name: 'view_4hse_equipment',
        description: 'Retrieves a single 4HSE equipment by ID. View complete equipment details including name, code, description, office, project, category, vendor, model, serial information. Requires OAuth2 authentication.'
    )]
    public function viewEquipment(
        #[Schema(
            type: 'string',
            description: 'Equipment ID (UUID format)'
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

            // Fetch equipment from 4HSE API
            $equipment = $client->view('equipment', $id);

            return [
                'success' => true,
                'equipment' => $equipment,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve equipment',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
