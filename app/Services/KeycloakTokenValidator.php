<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service for validating OAuth2 tokens with Keycloak
 */
class KeycloakTokenValidator
{
    /**
     * Validate an access token with Keycloak introspection endpoint
     *
     * @param string $token The access token to validate
     * @return array Token introspection response
     * @throws Exception If token validation fails
     */
    public function validate(string $token): array
    {
        // Cache key based on token hash
        $cacheKey = 'token_validation:' . hash('sha256', $token);

        Log::debug('Token validation requested', [
            'cache_key' => substr($cacheKey, 0, 32) . '...'
        ]);

        // Check cache first (tokens are short-lived, cache for 1 minute)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            if ($cached === false) {
                Log::warning('Token validation failed (from cache)');
                throw new Exception('Invalid or expired token (cached)');
            }
            Log::debug('Token validation succeeded (from cache)');
            return $cached;
        }

        try {
            Log::debug('Calling Keycloak introspection endpoint');

            $response = Http::asForm()
                ->when(!config('keycloak.verify_ssl'), fn($http) => $http->withoutVerifying())
                ->post(config('keycloak.endpoints.introspection'), [
                    'token' => $token,
                    'client_id' => config('keycloak.client_id'),
                    'client_secret' => config('keycloak.client_secret'),
                ]);

            if (!$response->successful()) {
                Log::error('Keycloak introspection failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                Cache::put($cacheKey, false, 60); // Cache failure for 1 minute
                throw new Exception('Token introspection failed: ' . $response->body());
            }

            $data = $response->json();

            // Check if token is active
            if (!($data['active'] ?? false)) {
                Log::warning('Token is not active', [
                    'username' => $data['preferred_username'] ?? 'unknown'
                ]);
                Cache::put($cacheKey, false, 60);
                throw new Exception('Token is not active or has expired');
            }

            // Validate client_id matches (audience validation)
            // Accept tokens from multiple allowed clients
            $allowedClients = [
                config('keycloak.client_id'), // mcp-server-4hse
                'service', // 4HSE Service frontend client
            ];

            $clientId = $data['client_id'] ?? $data['azp'] ?? null;
            if ($clientId && !in_array($clientId, $allowedClients)) {
                Log::error('Token audience mismatch', [
                    'client_id' => $clientId,
                    'allowed_clients' => $allowedClients
                ]);
                Cache::put($cacheKey, false, 60);
                throw new Exception('Token audience mismatch: ' . $clientId);
            }

            // Cache valid token data (cache until expiration, max 5 minutes)
            $exp = $data['exp'] ?? null;
            $ttl = $exp ? min($exp - time(), 300) : 60;
            if ($ttl > 0) {
                Cache::put($cacheKey, $data, $ttl);
            }

            Log::info('Token validated successfully', [
                'username' => $data['preferred_username'] ?? 'unknown',
                'client_id' => $clientId,
                'ttl' => $ttl
            ]);

            return $data;

        } catch (Exception $e) {
            Log::error('Token validation exception', [
                'error' => $e->getMessage()
            ]);
            Cache::put($cacheKey, false, 60);
            throw $e;
        }
    }

    /**
     * Get user information from token introspection data
     *
     * @param array $tokenData Token introspection response
     * @return array User information
     */
    public function getUserInfo(array $tokenData): array
    {
        return [
            'user_id' => $tokenData['sub'] ?? null,
            'username' => $tokenData['preferred_username'] ?? null,
            'email' => $tokenData['email'] ?? null,
            'name' => $tokenData['name'] ?? null,
            'scopes' => $tokenData['scope'] ?? '',
            'client_id' => $tokenData['client_id'] ?? $tokenData['azp'] ?? null,
        ];
    }

    /**
     * Extract bearer token from Authorization header
     *
     * @param string|null $authHeader Authorization header value
     * @return string|null The bearer token
     */
    public static function extractBearerToken(?string $authHeader): ?string
    {
        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
