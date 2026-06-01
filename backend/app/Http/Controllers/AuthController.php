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
     * POST /api/auth/register
     * Admin-only: creates a new user account.
     */
    public function register(Request $request)
    {
        $request->validate([
            'RoleNumber'  => 'required|integer|exists:Role,RoleNumber',
            'FullName'    => 'required|string|max:255',
            'Email'       => 'required|email|unique:User,Email',
            'Password'    => 'required|string|min:8',
            'PhoneNumber' => 'nullable|string|max:50',
            'Department'  => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'RoleNumber'  => $request->RoleNumber,
            'FullName'    => $request->FullName,
            'Email'       => $request->Email,
            'PasswordHash' => Hash::make($request->Password),
            'PhoneNumber' => $request->PhoneNumber,
            'Department'  => $request->Department,
            'IsActive'    => true,
            'CreatedDate' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => $this->userPayload($user->load('role')),
        ], 201);
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
            'RoleNumber' => $user->RoleNumber,
            'RoleName'   => $user->role?->RoleName,
        ];
    }
}
