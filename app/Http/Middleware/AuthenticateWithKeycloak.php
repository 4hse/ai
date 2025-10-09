<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\KeycloakTokenValidator;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * Middleware to authenticate requests using Keycloak OAuth2 Bearer tokens
 */
class AuthenticateWithKeycloak
{
    public function __construct(
        private KeycloakTokenValidator $tokenValidator
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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

            // Store authentication data in request for controllers
            $request->merge([
                'authenticated_user_id' => $userInfo['user_id'],
                'authenticated_username' => $userInfo['username'],
                'authenticated_email' => $userInfo['email'],
            ]);

            // Also store in attributes for flexibility
            $request->attributes->set('token_data', $tokenData);
            $request->attributes->set('user_info', $userInfo);
            $request->attributes->set('bearer_token', $token);

            return $next($request);

        } catch (Exception $e) {
            error_log("[API Auth] Token validation failed: " . $e->getMessage());
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
