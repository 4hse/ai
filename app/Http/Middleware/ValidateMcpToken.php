<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\KeycloakTokenValidator;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * Middleware to validate OAuth2 Bearer tokens for MCP requests
 */
readonly class ValidateMcpToken
{
    public function __construct(
        private KeycloakTokenValidator $tokenValidator
    ) {}

    /**
     * Handle an incoming request.
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
     * Return a 401 Unauthorized response with WWW-Authenticate header
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
