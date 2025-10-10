<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Exception;

/**
 * HTTP Client for 4HSE Service API
 */
class FourHseApiClient
{
    private string $baseUrl;
    private int $timeout;
    private int $retryTimes;
    private int $retryDelay;
    private bool $verifySSL;

    public function __construct(
        private readonly string $bearerToken
    ) {
        $this->baseUrl = config('fourhse.api.base_url');
        $this->timeout = config('fourhse.api.timeout');
        $this->retryTimes = config('fourhse.api.retry_times');
        $this->retryDelay = config('fourhse.api.retry_delay');
        $this->verifySSL = config('fourhse.api.verify_ssl');

        Log::debug('FourHseApiClient initialized', [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'retry_times' => $this->retryTimes
        ]);
    }

    /**
     * Generic index operation - List resources with optional filters
     *
     * @param string $resource Resource name (e.g., 'project', 'user')
     * @param array $params Request parameters (filter, per-page, page, sort, history)
     * @return array Response with data and pagination
     * @throws Exception
     */
    public function index(string $resource, array $params = []): array
    {
        Log::info("Fetching {$resource} list from 4HSE API", [
            'resource' => $resource,
            'params' => $params
        ]);

        $response = $this->request('POST', "/v2/{$resource}/index", $params);

        if (!$response->successful()) {
            Log::error("Failed to fetch {$resource} list", [
                'resource' => $resource,
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params
            ]);

            throw new Exception(
                "Failed to fetch {$resource} list: " . $response->body(),
                $response->status()
            );
        }

        // Extract pagination headers
        $pagination = $this->extractPaginationHeaders($response);

        Log::info("{$resource} list fetched successfully", [
            'resource' => $resource,
            'total_count' => $pagination['total_count'],
            'current_page' => $pagination['current_page'],
            'page_count' => $pagination['page_count']
        ]);

        return [
            'data' => $response->json(),
            'pagination' => $pagination,
        ];
    }

    /**
     * Generic view operation - Get a single resource by ID
     *
     * @param string $resource Resource name (e.g., 'project', 'user')
     * @param int|string $id Resource ID
     * @param array $params Optional request parameters
     * @return array Resource data
     * @throws Exception
     */
    public function view(string $resource, int|string $id, array $params = []): array
    {
        Log::info("Fetching {$resource} view from 4HSE API", [
            'resource' => $resource,
            'id' => $id,
            'params' => $params
        ]);

        // Build query string with ID and any additional params
        $queryParams = array_merge(['id' => $id], $params);
        $queryString = http_build_query($queryParams);

        $response = $this->request('GET', "/v2/{$resource}/view?{$queryString}");

        if (!$response->successful()) {
            Log::error("Failed to fetch {$resource} view", [
                'resource' => $resource,
                'id' => $id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception(
                "Failed to fetch {$resource} view: " . $response->body(),
                $response->status()
            );
        }

        Log::info("{$resource} view fetched successfully", [
            'resource' => $resource,
            'id' => $id
        ]);

        return $response->json();
    }

    /**
     * Generic create operation - Create a new resource
     *
     * @param string $resource Resource name (e.g., 'project', 'user')
     * @param array $data Resource data
     * @return array Created resource data
     * @throws Exception
     */
    public function create(string $resource, array $data): array
    {
        Log::info("Creating {$resource} via 4HSE API", [
            'resource' => $resource,
            'has_data' => !empty($data)
        ]);

        $response = $this->request('POST', "/v2/{$resource}/create", $data);

        if (!$response->successful()) {
            Log::error("Failed to create {$resource}", [
                'resource' => $resource,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception(
                "Failed to create {$resource}: " . $response->body(),
                $response->status()
            );
        }

        Log::info("{$resource} created successfully", [
            'resource' => $resource
        ]);

        return $response->json();
    }

    /**
     * Generic update operation - Update an existing resource
     *
     * @param string $resource Resource name (e.g., 'project', 'user')
     * @param int|string $id Resource ID or empty string if using alternative lookup
     * @param array $data Resource data to update
     * @param array $queryParams Additional query parameters (e.g., code, project_id for alternative lookup)
     * @return array Updated resource data
     * @throws Exception
     */
    public function update(string $resource, int|string $id, array $data, array $queryParams = []): array
    {
        Log::info("Updating {$resource} via 4HSE API", [
            'resource' => $resource,
            'id' => $id,
            'has_data' => !empty($data),
            'query_params' => $queryParams
        ]);

        // Build query string
        $params = $id ? ['id' => $id] : [];
        $params = array_merge($params, $queryParams);
        $queryString = http_build_query($params);

        $response = $this->request('PUT', "/v2/{$resource}/update?{$queryString}", $data);

        if (!$response->successful()) {
            Log::error("Failed to update {$resource}", [
                'resource' => $resource,
                'id' => $id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception(
                "Failed to update {$resource}: " . $response->body(),
                $response->status()
            );
        }

        Log::info("{$resource} updated successfully", [
            'resource' => $resource,
            'id' => $id
        ]);

        return $response->json();
    }

    /**
     * Generic delete operation - Delete a resource
     *
     * @param string $resource Resource name (e.g., 'project', 'user')
     * @param int|string $id Resource ID or empty string if using alternative lookup
     * @param array $queryParams Additional query parameters (e.g., code, project_id, force, historicize)
     * @return bool Success status
     * @throws Exception
     */
    public function delete(string $resource, int|string $id, array $queryParams = []): bool
    {
        Log::info("Deleting {$resource} via 4HSE API", [
            'resource' => $resource,
            'id' => $id,
            'query_params' => $queryParams
        ]);

        // Build query string
        $params = $id ? ['id' => $id] : [];
        $params = array_merge($params, $queryParams);
        $queryString = http_build_query($params);

        $response = $this->request('DELETE', "/v2/{$resource}/delete?{$queryString}");

        if (!$response->successful()) {
            Log::error("Failed to delete {$resource}", [
                'resource' => $resource,
                'id' => $id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception(
                "Failed to delete {$resource}: " . $response->body(),
                $response->status()
            );
        }

        Log::info("{$resource} deleted successfully", [
            'resource' => $resource,
            'id' => $id
        ]);

        return true;
    }

    /**
     * Make an HTTP request to 4HSE API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint path
     * @param array $data Request data
     * @return Response
     */
    private function request(string $method, string $endpoint, array $data = []): Response
    {
        Log::debug('Making API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'has_data' => !empty($data)
        ]);

        $http = $this->buildHttpClient();

        $response = $http->retry($this->retryTimes, $this->retryDelay)
            ->$method($this->baseUrl . $endpoint, $data);

        Log::debug('API request completed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status()
        ]);

        return $response;
    }

    /**
     * Build HTTP client with authentication and configuration
     */
    private function buildHttpClient(): PendingRequest
    {
        $http = Http::withToken($this->bearerToken)
            ->timeout($this->timeout)
            ->acceptJson()
            ->contentType('application/json');

        if (!$this->verifySSL) {
            $http = $http->withoutVerifying();
        }

        return $http;
    }

    /**
     * Extract pagination information from response headers
     */
    private function extractPaginationHeaders(Response $response): array
    {
        $headers = $response->headers();

        return [
            'current_page' => (int) ($headers['X-Pagination-Current-Page'][0] ?? 1),
            'page_count' => (int) ($headers['X-Pagination-Page-Count'][0] ?? 1),
            'per_page' => (int) ($headers['X-Pagination-Per-Page'][0] ?? 100),
            'total_count' => (int) ($headers['X-Pagination-Total-Count'][0] ?? 0),
        ];
    }

    /**
     * Get the bearer token being used
     */
    public function getBearerToken(): string
    {
        return $this->bearerToken;
    }
}
