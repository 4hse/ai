<?php

namespace App\Http\Middleware;

use App\Models\AuthorizedUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthorizedUser
{
    /**
     * Handle an incoming request.
     * Checks if the authenticated user is authorized to use AI features.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get authenticated user email (set by AuthenticateWithKeycloak middleware)
        $user_id = $request->input('authenticated_email');

        if (!$user_id) {
            return response()->json([
                'error' => 'unauthorized',
                'message' => 'User not authenticated',
            ], 401);
        }

        // Check if user is authorized
        if (!AuthorizedUser::where('user_id', $user_id)->exists()) {
            return response()->json([
                'error' => 'forbidden',
                'message' => 'User not authorized to use AI features',
            ], 403);
        }

        return $next($request);
    }
}
