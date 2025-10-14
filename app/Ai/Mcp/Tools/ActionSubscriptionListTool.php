<?php

namespace App\Ai\Mcp\Tools;

use App\Services\FourHseApiClient;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Throwable;

/**
 * Tool for listing 4HSE action subscriptions (the "needs" - assignments of requirements to people/resources)
 */
class ActionSubscriptionListTool
{
    /**
     * List 4HSE action subscriptions with optional filters.
     * Action subscriptions represent the "need" - they link people or resources to action requirements.
     * They create requirements that must later be satisfied by certificates.
     * Requires OAuth2 authentication.
     *
     * @param string|null $filterActionSubscriptionId Filter by action subscription ID (UUID)
     * @param string|null $filterActionId Filter by action ID (UUID) - the training course, maintenance plan, etc.
     * @param string|null $filterActionType Filter by action type (TRAINING=training courses, MAINTENANCE=maintenance plans, HEALTH=health surveillance, CHECK=procedures, PER=individual protection plans)
     * @param string|null $filterActionCode Filter by action code
     * @param string|null $filterActionName Filter by action name
     * @param string|null $filterSubscriberId Filter by subscriber ID (UUID) - the person or resource that needs the action
     * @param string|null $filterSubscriberType Filter by subscriber type - what type of resource needs the action
     * @param string|null $filterSubscriberCode Filter by subscriber code
     * @param string|null $filterSubscriberName Filter by subscriber name
     * @param string|null $filterStatus Filter by status
     * @param string|null $filterOfficeName Filter by office name
     * @param string|null $filterProjectName Filter by project name
     * @param string|null $filterProjectType Filter by project type
     * @param string|null $filterSubtenantId Filter by subtenant ID (UUID)
     * @param string|null $filterTenantId Filter by tenant ID (UUID)
     * @param int $perPage Number of results per page (default: 20, max: 100)
     * @param int $page Page number (default: 1)
     * @param string|null $sort Sort by field
     * @param bool $history Include historicized items (default: false)
     * @return array List of action subscriptions with pagination
     */
    #[
        McpTool(
            name: "list_4hse_action_subscriptions",
            description: 'List action subscriptions (the "needs" - assignments of training courses, maintenance plans, procedures, etc. to people/resources). Action subscriptions represent requirements that must be satisfied by certificates. Use this when user asks for "actions of [person/resource name]", "expired actions", "valid actions", "new actions", or to find which actions are subscribed/assigned to specific resources. Filter by subscriber name, action name, action type (TRAINING, HEALTH, MAINTENANCE, CHECK, PER), status: NEW (new/pending actions), VALID (valid/active actions), EXPIRED (expired/overdue/scadute actions). Requires OAuth2 authentication.',
        ),
    ]
    public function listActionSubscriptions(
        #[
            Schema(
                type: "string",
                description: "Filter by action subscription ID (UUID format)",
            ),
        ]
        ?string $filterActionSubscriptionId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by action ID (UUID format) - the training course, maintenance plan, procedure, etc.",
            ),
        ]
        ?string $filterActionId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by action type: TRAINING for training courses, MAINTENANCE for maintenance plans, HEALTH for health surveillance plans, CHECK for procedures, PER for individual protection plans",
                enum: ["TRAINING", "MAINTENANCE", "HEALTH", "CHECK", "PER"],
            ),
        ]
        ?string $filterActionType = null,

        #[
            Schema(type: "string", description: "Filter by action code"),
        ]
        ?string $filterActionCode = null,

        #[
            Schema(type: "string", description: "Filter by action name"),
        ]
        ?string $filterActionName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by subscriber ID (UUID format) - the person, equipment, or other resource that needs the action",
            ),
        ]
        ?string $filterSubscriberId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by subscriber type - what type of resource needs the action",
                enum: [
                    "PERSON",
                    "MATERIAL_ITEM",
                    "ROLE",
                    "WORK_GROUP",
                    "WORK_ENVIRONMENT",
                    "SUBSTANCE",
                    "EQUIPMENT",
                ],
            ),
        ]
        ?string $filterSubscriberType = null,

        #[
            Schema(type: "string", description: "Filter by subscriber code"),
        ]
        ?string $filterSubscriberCode = null,

        #[
            Schema(type: "string", description: "Filter by subscriber name"),
        ]
        ?string $filterSubscriberName = null,

        #[
            Schema(
                type: "string",
                description: "Filter by status",
                enum: ["NEW", "VALID", "EXPIRED"],
            ),
        ]
        ?string $filterStatus = null,

        #[
            Schema(type: "string", description: "Filter by office name"),
        ]
        ?string $filterOfficeName = null,

        #[
            Schema(type: "string", description: "Filter by project name"),
        ]
        ?string $filterProjectName = null,

        #[
            Schema(type: "string", description: "Filter by project type"),
        ]
        ?string $filterProjectType = null,

        #[
            Schema(
                type: "string",
                description: "Filter by subtenant ID (UUID format)",
            ),
        ]
        ?string $filterSubtenantId = null,

        #[
            Schema(
                type: "string",
                description: "Filter by tenant ID (UUID format)",
            ),
        ]
        ?string $filterTenantId = null,

        #[
            Schema(
                type: "integer",
                description: "Number of results per page",
                minimum: 1,
                maximum: 100,
            ),
        ]
        int $perPage = 20,

        #[
            Schema(type: "integer", description: "Page number", minimum: 1),
        ]
        int $page = 1,

        #[
            Schema(
                type: "string",
                description: "Sort by field",
                enum: [
                    "action_type",
                    "-action_type",
                    "action_code",
                    "-action_code",
                    "action_name",
                    "-action_name",
                    "subscriber_type",
                    "-subscriber_type",
                    "subscriber_code",
                    "-subscriber_code",
                    "subscriber_name",
                    "-subscriber_name",
                    "certificate_date_expire",
                    "-certificate_date_expire",
                    "date_latest_session",
                    "-date_latest_session",
                    "status",
                    "-status",
                    "office_name",
                    "-office_name",
                    "project_name",
                    "-project_name",
                ],
            ),
        ]
        ?string $sort = null,

        #[
            Schema(
                type: "boolean",
                description: "Include historicized items that are not currently valid",
            ),
        ]
        bool $history = false,
    ): array {
        try {
            // Get bearer token from app container (set by MCP middleware)
            $bearerToken = app()->has("mcp.bearer_token")
                ? app("mcp.bearer_token")
                : null;

            if (!$bearerToken) {
                return [
                    "error" => "Authentication required",
                    "message" =>
                        "This tool requires OAuth2 authentication. The bearer token was not found in the request context.",
                ];
            }

            // Build API client with user's OAuth2 token
            $client = new FourHseApiClient($bearerToken);

            // Build request parameters
            $params = [
                "per-page" => $perPage,
                "page" => $page,
                "history" => $history,
            ];

            // Add filters if provided
            $filter = [];
            if ($filterActionSubscriptionId !== null) {
                $filter["action_subscription_id"] = $filterActionSubscriptionId;
            }
            if ($filterActionId !== null) {
                $filter["action_id"] = $filterActionId;
            }
            if ($filterActionType !== null) {
                $filter["action_type"] = $filterActionType;
            }
            if ($filterActionCode !== null) {
                $filter["action_code"] = $filterActionCode;
            }
            if ($filterActionName !== null) {
                $filter["action_name"] = $filterActionName;
            }
            if ($filterSubscriberId !== null) {
                $filter["subscriber_id"] = $filterSubscriberId;
            }
            if ($filterSubscriberType !== null) {
                $filter["subscriber_type"] = $filterSubscriberType;
            }
            if ($filterSubscriberCode !== null) {
                $filter["subscriber_code"] = $filterSubscriberCode;
            }
            if ($filterSubscriberName !== null) {
                $filter["subscriber_name"] = $filterSubscriberName;
            }
            if ($filterStatus !== null) {
                $filter["status"] = $filterStatus;
            }
            if ($filterOfficeName !== null) {
                $filter["office_name"] = $filterOfficeName;
            }
            if ($filterProjectName !== null) {
                $filter["project_name"] = $filterProjectName;
            }
            if ($filterProjectType !== null) {
                $filter["project_type"] = $filterProjectType;
            }
            if ($filterSubtenantId !== null) {
                $filter["subtenant_id"] = $filterSubtenantId;
            }
            if ($filterTenantId !== null) {
                $filter["tenant_id"] = $filterTenantId;
            }

            if (!empty($filter)) {
                $params["filter"] = $filter;
            }

            // Add sort if provided
            if ($sort !== null) {
                $params["sort"] = $sort;
            }

            // Fetch action subscriptions from 4HSE API
            $result = $client->index("action-subscription", $params);

            return [
                "success" => true,
                "action_subscriptions" => $result["data"],
                "pagination" => $result["pagination"],
                "filters_applied" => $filter,
            ];
        } catch (Throwable $e) {
            return [
                "error" => "Failed to retrieve action subscriptions",
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
            ];
        }
    }
}
