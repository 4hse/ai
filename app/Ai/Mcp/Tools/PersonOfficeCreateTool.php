<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE person-office association
 */
class PersonOfficeCreateTool
{
    /**
     * Create a new person-office association in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $officeId Office ID (UUID)
     * @param string $personId Person ID (UUID)
     * @param string $projectId Project ID (UUID)
     * @return array Created person-office association details
     */
    #[McpTool(
        name: 'create_4hse_person_office',
        description: 'Creates a new person-office association in 4HSE. Requires OAuth2 authentication.'
    )]
    public function createPersonOffice(
        #[Schema(
            type: 'string',
            description: 'Office ID in UUID format (required)'
        )]
        string $officeId,

        #[Schema(
            type: 'string',
            description: 'Person ID in UUID format (required)'
        )]
        string $personId,

        #[Schema(
            type: 'string',
            description: 'Project ID in UUID format (required)'
        )]
        string $projectId
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

            // Build person-office data
            $data = [
                'office_id' => $officeId,
                'person_id' => $personId,
                'project_id' => $projectId,
            ];

            // Create person-office via 4HSE API
            $personOffice = $client->create('person-office', $data);

            return [
                'success' => true,
                'person_office' => $personOffice,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to create person-office association',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
