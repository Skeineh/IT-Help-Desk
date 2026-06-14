<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     * Returns JWT token + user info (with role).
     */
    public function login(Request $request)
    {
        $request->validate([
            'Email'    => 'required|email',
            'Password' => 'required|string',
        ]);

        $user = User::where('Email', $request->Email)->first();

        if (!$user || !Hash::check($request->Password, $user->PasswordHash)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->IsActive) {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not create token.'], 500);
        }

        $now = now()->toDateTimeString();

        $user->LastLoginDate = $now;
        $user->save();

        ActivityLog::create([
            'UserNumber'            => $user->UserNumber,
            'ActionType'            => 'Login',
            'EntityType'            => 'User',
            'EntityReferenceNumber' => $user->UserNumber,
            'ActionDescription'     => 'User logged in successfully.',
            'IpAddress'             => $request->ip(),
            'CreatedDate'           => $now,
        ]);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * POST /api/auth/logout
     * Invalidates the current JWT token.
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['message' => 'Failed to invalidate token.'], 500);
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/auth/me
     * Returns the authenticated user with role.
     */
    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(['user' => $this->userPayload($user)]);
    }

    /**
     * POST /api/change-password
     * Authenticated users must provide their current password and a confirmed new password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        if (!Hash::check($validated['current_password'], $user->PasswordHash)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors' => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        $user->PasswordHash = Hash::make($validated['new_password']);
        $user->MustChangePassword = false;
        $user->save();

        ActivityLog::create([
            'UserNumber'            => $user->UserNumber,
            'ActionType'            => 'PasswordChanged',
            'EntityType'            => 'User',
            'EntityReferenceNumber' => $user->UserNumber,
            'ActionDescription'     => 'User changed their password.',
            'IpAddress'             => $request->ip(),
            'CreatedDate'           => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
            'user' => $this->userPayload($user->load('role')),
        ]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('role');

        return [
            'UserNumber' => $user->UserNumber,
            'FullName'   => $user->FullName,
            'Email'      => $user->Email,
            'Department' => $user->Department,
            'PhoneNumber' => $user->PhoneNumber,
            'IsActive'   => $user->IsActive,
            'MustChangePassword' => (bool) $user->MustChangePassword,
            'must_change_password' => (bool) $user->MustChangePassword,
            'RoleNumber' => $user->RoleNumber,
            'RoleName'   => $user->role?->RoleName,
        ];
    }
}
