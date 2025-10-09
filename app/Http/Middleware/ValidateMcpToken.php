<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\KeycloakTokenValidator;
use Symfony\Component\HttpFoundation\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response as ReactResponse;
use React\Promise\PromiseInterface;
use Exception;

/**
 * Middleware to validate OAuth2 Bearer tokens for MCP requests
 * Supports both Laravel HTTP stack and PSR-15/PSR-7 for MCP server
 */
readonly class ValidateMcpToken
{
    private ?KeycloakTokenValidator $tokenValidator;

    public function __construct(?KeycloakTokenValidator $tokenValidator = null)
    {
        $this->tokenValidator = $tokenValidator ?? app(KeycloakTokenValidator::class);
    }

    /**
     * PSR-15 middleware invocation for MCP server
     */
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        // Extract Bearer token from Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if (!$token) {
            error_log("[MCP Auth] Missing authorization token from " . $request->getUri()->getPath());
            return new ReactResponse(
                401,
                [
                    'Content-Type' => 'application/json',
                    'WWW-Authenticate' => sprintf(
                        'Bearer realm="%s", error="invalid_token", error_description="Missing authorization token"',
                        config('app.name')
                    ),
                ],
                json_encode([
                    'error' => 'unauthorized',
                    'message' => 'Missing authorization token',
                ])
            );
        }

        try {
            // Validate token with Keycloak
            $tokenData = $this->tokenValidator->validate($token);

            // Extract user information
            $userInfo = $this->tokenValidator->getUserInfo($tokenData);

            // Store token data and user info in request attributes
            $request = $request
                ->withAttribute('token_data', $tokenData)
                ->withAttribute('user_info', $userInfo)
                ->withAttribute('bearer_token', $token)
                ->withAttribute('user_id', $userInfo['user_id'] ?? null)
                ->withAttribute('username', $userInfo['username'] ?? null);

            $result = $next($request);

            return match (true) {
                $result instanceof PromiseInterface => $result->then(fn($response) => $this->addAuthHeader($response)),
                $result instanceof ResponseInterface => $this->addAuthHeader($result),
                default => $result
            };

        } catch (Exception $e) {
            error_log("[MCP Auth] Token validation FAILED: " . $e->getMessage());
            return new ReactResponse(
                401,
                [
                    'Content-Type' => 'application/json',
                    'WWW-Authenticate' => sprintf(
                        'Bearer realm="%s", error="invalid_token", error_description="%s"',
                        config('app.name'),
                        $e->getMessage()
                    ),
                ],
                json_encode([
                    'error' => 'unauthorized',
                    'message' => $e->getMessage(),
                ])
            );
        }
    }

    /**
     * Laravel HTTP middleware handler (for web routes)
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract Bearer token from Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return $this->unauthorizedResponse('Missing authorization token');
        }

        try {
            // Validate token with Keycloak
            $tokenData = $this->tokenValidator->validate($token);

            // Extract user information
            $userInfo = $this->tokenValidator->getUserInfo($tokenData);

            // Store token data and user info in request for later use
            $request->attributes->set('token_data', $tokenData);
            $request->attributes->set('user_info', $userInfo);
            $request->attributes->set('bearer_token', $token);

            // Optionally set user_id for easier access
            $request->merge([
                'authenticated_user_id' => $userInfo['user_id'],
                'authenticated_username' => $userInfo['username'],
            ]);

            return $next($request);

        } catch (Exception $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }
    }

    /**
     * Add authentication provider header to PSR-7 response
     */
    private function addAuthHeader($response)
    {
        return $response instanceof ResponseInterface
            ? $response->withHeader('X-Auth-Provider', 'keycloak')
            : $response;
    }

    /**
     * Return a 401 Unauthorized response with WWW-Authenticate header (Laravel)
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $message,
        ], 401, [
            'WWW-Authenticate' => sprintf(
                'Bearer realm="%s", error="invalid_token", error_description="%s"',
                config('app.name'),
                $message
            ),
        ]);
    }
}
