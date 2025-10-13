<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE work group persons
 */
class WorkGroupPersonListTool
{
    /**
     * List 4HSE work group persons with optional filters.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterWorkGroupPersonId Filter by work group person ID
     * @param string|null $filterWorkGroupId Filter by work group ID
     * @param string|null $filterPersonOfficeId Filter by person office ID
     * @param string|null $filterWorkGroupCode Filter by work group code
     * @param string|null $filterWorkGroupName Filter by work group name
     * @param string|null $filterWorkGroupType Filter by work group type
     * @param string|null $filterPersonId Filter by person ID
     * @param string|null $filterPersonCode Filter by person code
     * @param string|null $filterPersonFirstName Filter by person first name
     * @param string|null $filterPersonLastName Filter by person last name
     * @param bool|null $filterPersonIsEmployee Filter by person is employee
     * @param bool|null $filterPersonIsExternal Filter by person is external
     * @param string|null $filterProjectId Filter by project ID
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param string|null $filterOfficeId Filter by office ID
     * @param string|null $filterOfficeName Filter by office name
     * @param string|null $filterOfficeCode Filter by office code
     * @param int $perPage Number of results per page (default: 100, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicist items (default: false)
     * @return array List of work group persons with pagination
     */
    #[McpTool(
        name: 'list_4hse_work_group_persons',
        description: 'List work group person associations in 4HSE. Use this to find associations between work groups and people. Filter by work group, person details, office, project. Requires OAuth2 authentication.'
    )]
    public function listWorkGroupPersons(
        #[Schema(
            type: 'string',
            description: 'Filter by work group person ID (UUID format)'
        )]
        ?string $filterWorkGroupPersonId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by work group ID (UUID format)'
        )]
        ?string $filterWorkGroupId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by person office ID (UUID format)'
        )]
        ?string $filterPersonOfficeId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by work group code'
        )]
        ?string $filterWorkGroupCode = null,

        #[Schema(
            type: 'string',
            description: 'Filter by work group name'
        )]
        ?string $filterWorkGroupName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by work group type'
        )]
        ?string $filterWorkGroupType = null,

        #[Schema(
            type: 'string',
            description: 'Filter by person ID (UUID format)'
        )]
        ?string $filterPersonId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by person code'
        )]
        ?string $filterPersonCode = null,

        #[Schema(
            type: 'string',
            description: 'Filter by person first name'
        )]
        ?string $filterPersonFirstName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by person last name'
        )]
        ?string $filterPersonLastName = null,

        #[Schema(
            type: 'boolean',
            description: 'Filter by person is employee'
        )]
        ?bool $filterPersonIsEmployee = null,

        #[Schema(
            type: 'boolean',
            description: 'Filter by person is external'
        )]
        ?bool $filterPersonIsExternal = null,

        #[Schema(
            type: 'string',
            description: 'Filter by project ID (UUID format)'
        )]
        ?string $filterProjectId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by project name'
        )]
        ?string $filterProjectName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by project type'
        )]
        ?string $filterProjectType = null,

        #[Schema(
            type: 'string',
            description: 'Filter by office ID (UUID format)'
        )]
        ?string $filterOfficeId = null,

        #[Schema(
            type: 'string',
            description: 'Filter by office name'
        )]
        ?string $filterOfficeName = null,

        #[Schema(
            type: 'string',
            description: 'Filter by office code'
        )]
        ?string $filterOfficeCode = null,

        #[Schema(
            type: 'integer',
            description: 'Number of results per page',
            minimum: 1,
            maximum: 100
        )]
        int $perPage = 100,

        #[Schema(
            type: 'integer',
            description: 'Page number',
            minimum: 1
        )]
        int $page = 1,

        #[Schema(
            type: 'string',
            description: 'Sort by field',
            enum: ['work_group_code', '-work_group_code', 'work_group_name', '-work_group_name', 'work_group_type', '-work_group_type', 'person_code', '-person_code', 'person_first_name', '-person_first_name', 'person_last_name', '-person_last_name']
        )]
        ?string $sort = null,

        #[Schema(
            type: 'boolean',
            description: 'Include historicist items that are not currently valid'
        )]
        bool $history = false
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

            // Build request parameters
            $params = [
                'per-page' => $perPage,
                'page' => $page,
                'history' => $history,
            ];

            // Add filters if provided
            $filter = [];
            if ($filterWorkGroupPersonId !== null) {
                $filter['work_group_person_id'] = $filterWorkGroupPersonId;
            }
            if ($filterWorkGroupId !== null) {
                $filter['work_group_id'] = $filterWorkGroupId;
            }
            if ($filterPersonOfficeId !== null) {
                $filter['person_office_id'] = $filterPersonOfficeId;
            }
            if ($filterWorkGroupCode !== null) {
                $filter['work_group_code'] = $filterWorkGroupCode;
            }
            if ($filterWorkGroupName !== null) {
                $filter['work_group_name'] = $filterWorkGroupName;
            }
            if ($filterWorkGroupType !== null) {
                $filter['work_group_type'] = $filterWorkGroupType;
            }
            if ($filterPersonId !== null) {
                $filter['person_id'] = $filterPersonId;
            }
            if ($filterPersonCode !== null) {
                $filter['person_code'] = $filterPersonCode;
            }
            if ($filterPersonFirstName !== null) {
                $filter['person_first_name'] = $filterPersonFirstName;
            }
            if ($filterPersonLastName !== null) {
                $filter['person_last_name'] = $filterPersonLastName;
            }
            if ($filterPersonIsEmployee !== null) {
                $filter['person_is_employee'] = $filterPersonIsEmployee;
            }
            if ($filterPersonIsExternal !== null) {
                $filter['person_is_external'] = $filterPersonIsExternal;
            }
            if ($filterProjectId !== null) {
                $filter['project_id'] = $filterProjectId;
            }
            if ($filterProjectName !== null) {
                $filter['project_name'] = $filterProjectName;
            }
            if ($filterProjectType !== null) {
                $filter['project_type'] = $filterProjectType;
            }
            if ($filterOfficeId !== null) {
                $filter['office_id'] = $filterOfficeId;
            }
            if ($filterOfficeName !== null) {
                $filter['office_name'] = $filterOfficeName;
            }
            if ($filterOfficeCode !== null) {
                $filter['office_code'] = $filterOfficeCode;
            }

            if (!empty($filter)) {
                $params['filter'] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params['sort'] = $sort;
            }

            // Fetch work group persons from 4HSE API
            $result = $client->index('work-group-person', $params);

            return [
                'success' => true,
                'work_group_persons' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => $filter,
            ];

        } catch (Throwable $e) {
            return [
                'error' => 'Failed to retrieve work group persons',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
