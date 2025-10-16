<?php

namespace App\Http\Controllers;

use App\Models\AuthorizedUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorizedUserController extends Controller
{
    /**
     * Check if the authenticated user is authorized to use AI features
     */
    public function checkAuthorization(Request $request): JsonResponse
    {
        // Get authenticated user email (used as user_id)
        $user_id = $request->input('authenticated_email');

        // Check if user exists in authorized_users table
        $isAuthorized = AuthorizedUser::where('user_id', $user_id)->exists();

        return response()->json([
            'success' => true,
            'authorized' => $isAuthorized,
            'user_id' => $user_id,
        ]);
    }
}
