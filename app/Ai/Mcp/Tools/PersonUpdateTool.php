<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for updating an existing 4HSE person
 */
class PersonUpdateTool
{
    /**
     * Update an existing person in 4HSE by ID or by code+project_id.
     * Requires OAuth2 authentication.
     *
     * @param string|null $id Person ID (UUID). Required if code and projectId are not provided.
     * @param string|null $code Person code. Required together with projectId if id is not provided.
     * @param string|null $projectId Project ID (UUID). Required together with code if id is not provided.
     * @param string|null $firstName Person's first name (max 70 chars)
     * @param string|null $lastName Person's last name (max 70 chars)
     * @param string|null $birthDate Birth date (format: YYYY-MM-DD)
     * @param string|null $entityId Entity ID
     * @param string|null $relatedUser Related user identifier
     * @param string|null $newCode New person code (max 50 chars)
     * @param string|null $street Street address (max 255 chars)
     * @param string|null $locality Locality (max 50 chars)
     * @param string|null $postalCode Postal code (max 50 chars)
     * @param string|null $region Region (max 10 chars)
     * @param string|null $sex Sex (max 10 chars)
     * @param string|null $country Country (max 50 chars)
     * @param string|null $birthPlace Birth place (max 150 chars)
     * @param string|null $taxCode Tax code (max 30 chars)
     * @param string|null $note Additional notes
     * @param string|null $contractType Contract type
     * @param int|null $isEmployee Is employee (0 or 1)
     * @param int|null $isPreventionPeople Is prevention people (0 or 1)
     * @return array Updated person details
     */
    #[McpTool(
        name: 'update_4hse_person',
        description: 'Updates an existing person in 4HSE by ID or by code+project_id. Requires OAuth2 authentication.'
    )]
    public function updatePerson(
        #[Schema(
            type: 'string',
            description: 'Person ID (UUID format). Required if code and projectId are not provided.'
        )]
        ?string $id = null,

        #[Schema(
            type: 'string',
            description: 'Person code. Required together with projectId if id is not provided.'
        )]
        ?string $code = null,

        #[Schema(
            type: 'string',
            description: 'Project ID (UUID format). Required together with code if id is not provided.'
        )]
        ?string $projectId = null,

        #[Schema(
            type: 'string',
            description: "Person's first name (max 70 characters)"
        )]
        ?string $firstName = null,

        #[Schema(
            type: 'string',
            description: "Person's last name (max 70 characters)"
        )]
        ?string $lastName = null,

        #[Schema(
            type: 'string',
            description: 'Birth date in YYYY-MM-DD format'
        )]
        ?string $birthDate = null,

        #[Schema(
            type: 'string',
            description: 'Entity ID'
        )]
        ?string $entityId = null,

        #[Schema(
            type: 'string',
            description: 'Related user identifier'
        )]
        ?string $relatedUser = null,

        #[Schema(
            type: 'string',
            description: 'New person code (max 50 characters)'
        )]
        ?string $newCode = null,

        #[Schema(
            type: 'string',
            description: 'Street address (max 255 characters)'
        )]
        ?string $street = null,

        #[Schema(
            type: 'string',
            description: 'Locality (max 50 characters)'
        )]
        ?string $locality = null,

        #[Schema(
            type: 'string',
            description: 'Postal code (max 50 characters)'
        )]
        ?string $postalCode = null,

        #[Schema(
            type: 'string',
            description: 'Region (max 10 characters)'
        )]
        ?string $region = null,

        #[Schema(
            type: 'string',
            description: 'Sex (max 10 characters)'
        )]
        ?string $sex = null,

        #[Schema(
            type: 'string',
            description: 'Country (max 50 characters)'
        )]
        ?string $country = null,

        #[Schema(
            type: 'string',
            description: 'Birth place (max 150 characters)'
        )]
        ?string $birthPlace = null,

        #[Schema(
            type: 'string',
            description: 'Tax code (max 30 characters)'
        )]
        ?string $taxCode = null,

        #[Schema(
            type: 'string',
            description: 'Additional notes'
        )]
        ?string $note = null,

        #[Schema(
            type: 'string',
            description: 'Contract type'
        )]
        ?string $contractType = null,

        #[Schema(
            type: 'integer',
            description: 'Is employee (0 or 1)',
            enum: [0, 1]
        )]
        ?int $isEmployee = null,

        #[Schema(
            type: 'integer',
            description: 'Is prevention people (0 or 1)',
            enum: [0, 1]
        )]
        ?int $isPreventionPeople = null
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

            // Validate parameters
            if (!$id && (!$code || !$projectId)) {
                return [
                    'error' => 'Invalid parameters',
                    'message' => 'Either id must be provided, or both code and projectId must be provided.',
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build person data with only provided fields
            $data = [];

            if ($firstName !== null) {
                $data['first_name'] = $firstName;
            }
            if ($lastName !== null) {
                $data['last_name'] = $lastName;
            }
            if ($birthDate !== null) {
                $data['birth_date'] = $birthDate;
            }
            if ($entityId !== null) {
                $data['entity_id'] = $entityId;
            }
            if ($relatedUser !== null) {
                $data['related_user'] = $relatedUser;
            }
            if ($newCode !== null) {
                $data['code'] = $newCode;
            }
            if ($street !== null) {
                $data['street'] = $street;
            }
            if ($locality !== null) {
                $data['locality'] = $locality;
            }
            if ($postalCode !== null) {
                $data['postal_code'] = $postalCode;
            }
            if ($region !== null) {
                $data['region'] = $region;
            }
            if ($sex !== null) {
                $data['sex'] = $sex;
            }
            if ($country !== null) {
                $data['country'] = $country;
            }
            if ($birthPlace !== null) {
                $data['birth_place'] = $birthPlace;
            }
            if ($taxCode !== null) {
                $data['tax_code'] = $taxCode;
            }
            if ($note !== null) {
                $data['note'] = $note;
            }
            if ($contractType !== null) {
                $data['contract_type'] = $contractType;
            }
            if ($isEmployee !== null) {
                $data['is_employee'] = $isEmployee;
            }
            if ($isPreventionPeople !== null) {
                $data['is_prevention_people'] = $isPreventionPeople;
            }

            // Build query parameters for alternative lookup
            $queryParams = [];
            if (!$id) {
                $queryParams['code'] = $code;
                $queryParams['project_id'] = $projectId;
                // For update, we need to append query params to the ID
                $personIdentifier = 'lookup?' . http_build_query($queryParams);
            } else {
                $personIdentifier = $id;
            }

            // Update person via 4HSE API
            $person = $client->update('person', $personIdentifier, $data);

            return [
                'success' => true,
                'person' => $person,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to update person',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
