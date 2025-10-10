<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for creating a new 4HSE certificate
 */
class CertificateCreateTool
{
    /**
     * Create a new certificate in 4HSE.
     * Requires OAuth2 authentication.
     *
     * @param string $dateRelease Release date (format: YYYY-MM-DD)
     * @param string $name Certificate name
     * @param string $actionType Action type
     * @param string $resourceId Resource ID
     * @param string $tenantId Tenant ID (UUID)
     * @param string|null $dateExpire Expiration date (format: YYYY-MM-DD)
     * @param string|null $note Additional notes
     * @param array|null $data Additional data (JSON object)
     * @param int|null $warning Warning status (0 or 1)
     * @param string|null $validityUnit Validity unit
     * @param int|null $validity Validity period
     * @return array Created certificate details
     */
    #[McpTool(
        name: 'create_4hse_certificate',
        description: 'Creates a new certificate in 4HSE. Requires OAuth2 authentication.'
    )]
    public function createCertificate(
        #[Schema(
            type: 'string',
            description: 'Release date in YYYY-MM-DD format (required)'
        )]
        string $dateRelease,

        #[Schema(
            type: 'string',
            description: 'Certificate name (required, max 255 characters)'
        )]
        string $name,

        #[Schema(
            type: 'string',
            description: 'Action type (required)',
            enum: ['TRAINING', 'MAINTENANCE', 'HEALTH', 'CHECK', 'PER']
        )]
        string $actionType,

        #[Schema(
            type: 'string',
            description: 'Resource ID in UUID format (required): ID of the person, material, equipment, etc. the certificate is for'
        )]
        string $resourceId,

        #[Schema(
            type: 'string',
            description: 'Tenant ID in UUID format (required)'
        )]
        string $tenantId,

        #[Schema(
            type: 'string',
            description: 'Expiration date in YYYY-MM-DD format'
        )]
        ?string $dateExpire = null,

        #[Schema(
            type: 'string',
            description: 'Additional notes'
        )]
        ?string $note = null,

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

            // Build certificate data
            $certificateData = [
                'date_release' => $dateRelease,
                'name' => $name,
                'action_type' => $actionType,
                'resource_id' => $resourceId,
                'tenant_id' => $tenantId,
            ];

            // Add optional fields if provided
            if ($dateExpire !== null) {
                $certificateData['date_expire'] = $dateExpire;
            }
            if ($note !== null) {
                $certificateData['note'] = $note;
            }
            if ($data !== null) {
                $certificateData['data'] = $data;
            }
            if ($warning !== null) {
                $certificateData['warning'] = $warning;
            }
            if ($validityUnit !== null) {
                $certificateData['validity_unit'] = $validityUnit;
            }
            if ($validity !== null) {
                $certificateData['validity'] = $validity;
            }

            // Create certificate via 4HSE API
            $certificate = $client->create('certificate', $certificateData);

            return [
                'success' => true,
                'certificate' => $certificate,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to create certificate',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
