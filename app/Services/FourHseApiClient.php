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
     * Get list of projects with optional filters
     *
     * @param array $params Request parameters (filter, per-page, page, sort, history)
     * @return array Response with projects and pagination
     * @throws Exception
     */
    public function getProjects(array $params = []): array
    {
        Log::info('Fetching projects from 4HSE API', [
            'params' => $params
        ]);

        $response = $this->request('POST', '/v2/project/index', $params);

        if (!$response->successful()) {
            Log::error('Failed to fetch projects', [
                'status' => $response->status(),
                'body' => $response->body(),
                'params' => $params
            ]);

            throw new Exception(
                'Failed to fetch projects: ' . $response->body(),
                $response->status()
            );
        }

        // Extract pagination headers
        $pagination = $this->extractPaginationHeaders($response);

        Log::info('Projects fetched successfully', [
            'total_count' => $pagination['total_count'],
            'current_page' => $pagination['current_page'],
            'page_count' => $pagination['page_count']
        ]);

        return [
            'projects' => $response->json(),
            'pagination' => $pagination,
        ];
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
