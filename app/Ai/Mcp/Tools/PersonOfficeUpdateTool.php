<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE person-office association
 */
class PersonOfficeUpdateTool
{
    /**
     * Update an existing person-office association in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Person-office association ID (UUID)
     * @param string|null $officeId Office ID (UUID)
     * @param string|null $personId Person ID (UUID)
     * @param string|null $projectId Project ID (UUID)
     * @return array Updated person-office association details
     */
    #[McpTool(
        name: 'update_4hse_person_office',
        description: 'Updates an existing person-office association in 4HSE. Requires OAuth2 authentication.'
    )]
    public function updatePersonOffice(
        #[Schema(
            type: 'string',
            description: 'Person-office association ID in UUID format (required)'
        )]
        string $id,

        #[Schema(
            type: 'string',
            description: 'Office ID in UUID format'
        )]
        ?string $officeId = null,

        #[Schema(
            type: 'string',
            description: 'Person ID in UUID format'
        )]
        ?string $personId = null,

        #[Schema(
            type: 'string',
            description: 'Project ID in UUID format'
        )]
        ?string $projectId = null
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

            // Build person-office data with only provided fields
            $data = [];

            if ($officeId !== null) {
                $data['office_id'] = $officeId;
            }
            if ($personId !== null) {
                $data['person_id'] = $personId;
            }
            if ($projectId !== null) {
                $data['project_id'] = $projectId;
            }

            // Update person-office via 4HSE API
            $personOffice = $client->update('person-office', $id, $data);

            return [
                'success' => true,
                'person_office' => $personOffice,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to update person-office association',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
