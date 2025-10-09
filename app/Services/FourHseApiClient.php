<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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
        private string $bearerToken
    ) {
        $this->baseUrl = config('fourhse.api.base_url');
        $this->timeout = config('fourhse.api.timeout');
        $this->retryTimes = config('fourhse.api.retry_times');
        $this->retryDelay = config('fourhse.api.retry_delay');
        $this->verifySSL = config('fourhse.api.verify_ssl');
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
        $response = $this->request('POST', '/v2/project/index', $params);

        if (!$response->successful()) {
            throw new Exception(
                'Failed to fetch projects: ' . $response->body(),
                $response->status()
            );
        }

        // Extract pagination headers
        $pagination = $this->extractPaginationHeaders($response);

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
        $http = $this->buildHttpClient();

        return $http->retry($this->retryTimes, $this->retryDelay)
            ->$method($this->baseUrl . $endpoint, $data);
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
