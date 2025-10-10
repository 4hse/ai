<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE certificate
 */
class CertificateUpdateTool
{
    /**
     * Update an existing certificate in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $id Certificate ID (UUID)
     * @param string|null $dateRelease Release date (format: YYYY-MM-DD)
     * @param string|null $dateExpire Expiration date (format: YYYY-MM-DD)
     * @param string|null $name Certificate name
     * @param string|null $note Additional notes
     * @param string|null $actionType Action type
     * @param string|null $resourceId Resource ID
     * @param array|null $data Additional data (JSON object)
     * @param int|null $warning Warning status (0 or 1)
     * @param string|null $tenantId Tenant ID (UUID)
     * @param string|null $validityUnit Validity unit
     * @param int|null $validity Validity period
     * @return array Updated certificate details
     */
    #[McpTool(
        name: 'update_4hse_certificate',
        description: 'Updates an existing certificate in 4HSE. Requires OAuth2 authentication.'
    )]
    public function updateCertificate(
        #[Schema(
            type: 'string',
            description: 'Certificate ID in UUID format (required)'
        )]
        string $id,

        #[Schema(
            type: 'string',
            description: 'Release date in YYYY-MM-DD format'
        )]
        ?string $dateRelease = null,

        #[Schema(
            type: 'string',
            description: 'Expiration date in YYYY-MM-DD format'
        )]
        ?string $dateExpire = null,

        #[Schema(
            type: 'string',
            description: 'Certificate name (max 255 characters)'
        )]
        ?string $name = null,

        #[Schema(
            type: 'string',
            description: 'Additional notes'
        )]
        ?string $note = null,

        #[Schema(
            type: 'string',
            description: 'Action type',
            enum: ['TRAINING', 'MAINTENANCE', 'HEALTH', 'CHECK', 'PER']
        )]
        ?string $actionType = null,

        #[Schema(
            type: 'string',
            description: 'Resource ID in UUID format: ID of the person, material, equipment, etc. the certificate is for'
        )]
        ?string $resourceId = null,

        #[Schema(
            type: 'object',
            description: 'Additional data (JSON object)'
        )]
        ?array $data = null,

        #[Schema(
            type: 'integer',
            description: 'Warning status',
            enum: [0, 1]
        )]
        ?int $warning = null,

        #[Schema(
            type: 'string',
            description: 'Tenant ID in UUID format'
        )]
        ?string $tenantId = null,

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
        ?int $validity = null
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

            // Build certificate data with only provided fields
            $certificateData = [];

            if ($dateRelease !== null) {
                $certificateData['date_release'] = $dateRelease;
            }
            if ($dateExpire !== null) {
                $certificateData['date_expire'] = $dateExpire;
            }
            if ($name !== null) {
                $certificateData['name'] = $name;
            }
            if ($note !== null) {
                $certificateData['note'] = $note;
            }
            if ($actionType !== null) {
                $certificateData['action_type'] = $actionType;
            }
            if ($resourceId !== null) {
                $certificateData['resource_id'] = $resourceId;
            }
            if ($data !== null) {
                $certificateData['data'] = $data;
            }
            if ($warning !== null) {
                $certificateData['warning'] = $warning;
            }
            if ($tenantId !== null) {
                $certificateData['tenant_id'] = $tenantId;
            }
            if ($validityUnit !== null) {
                $certificateData['validity_unit'] = $validityUnit;
            }
            if ($validity !== null) {
                $certificateData['validity'] = $validity;
            }

            // Update certificate via 4HSE API
            $certificate = $client->update('certificate', $id, $certificateData);

            return [
                'success' => true,
                'certificate' => $certificate,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to update certificate',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
