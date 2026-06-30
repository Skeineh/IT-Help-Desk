<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Mail\WelcomeCredentialsMail;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    use AuthorizesTickets;

    public function roles()
    {
        return response()->json(
            Role::orderBy('RoleNumber')->get(['RoleNumber', 'RoleName', 'Description'])
        );
    }

    public function store(Request $request)
    {
        $admin = $this->resolveUser();

        $request->merge([
            'FullName' => $request->input('FullName', $request->input('name')),
            'Email' => $request->input('Email', $request->input('email')),
            'Password' => $request->input('Password', $request->input('temporary_password')),
            'RoleNumber' => $request->input('RoleNumber'),
        ]);

        if (!$request->filled('RoleNumber') && $request->filled('role')) {
            $role = Role::where('RoleName', $request->input('role'))->first();
            if ($role) {
                $request->merge(['RoleNumber' => $role->RoleNumber]);
            }
        }

        $validated = $request->validate([
            'RoleNumber' => ['required', 'integer', Rule::exists('Role', 'RoleNumber')],
            'FullName' => ['required', 'string', 'max:100'],
            'Email' => ['required', 'email', 'max:100', Rule::unique('User', 'Email')],
            'Password' => ['required', 'string', 'min:8'],
            'PhoneNumber' => ['nullable', 'string', 'max:20'],
            'Department' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'RoleNumber' => $validated['RoleNumber'],
            'FullName' => $validated['FullName'],
            'Email' => $validated['Email'],
            'PasswordHash' => Hash::make($validated['Password']),
            'PhoneNumber' => $validated['PhoneNumber'] ?? null,
            'Department' => $validated['Department'] ?? null,
            'IsActive' => true,
            'MustChangePassword' => true,
            'CreatedDate' => now()->toDateTimeString(),
        ]);

        ActivityLog::create([
            'UserNumber' => $admin->UserNumber,
            'ActionType' => 'UserCreated',
            'EntityType' => 'User',
            'EntityReferenceNumber' => $user->UserNumber,
            'ActionDescription' => "Admin created user {$user->Email}.",
            'CreatedDate' => now()->toDateTimeString(),
        ]);

        $emailSent = true;
        $emailError = null;
        try {
            Mail::to($user->Email)->send(
                new WelcomeCredentialsMail($user->FullName, $user->Email, $validated['Password'])
            );
        } catch (\Throwable $e) {
            $emailSent = false;
            $emailError = $e->getMessage();
            \Log::error('Welcome email failed', ['to' => $user->Email, 'error' => $emailError]);
        }

        return response()->json([
            'message' => 'User created successfully. They must change password on first login.',
            'user' => $this->userPayload($user->load('role')),
            'email_sent' => $emailSent,
            'email_error' => $emailError,
        ], 201);
    }

    private function userPayload(User $user): array
    {
        return [
            'UserNumber' => $user->UserNumber,
            'FullName' => $user->FullName,
            'Email' => $user->Email,
            'Department' => $user->Department,
            'PhoneNumber' => $user->PhoneNumber,
            'IsActive' => (bool) $user->IsActive,
            'MustChangePassword' => (bool) $user->MustChangePassword,
            'must_change_password' => (bool) $user->MustChangePassword,
            'RoleNumber' => $user->RoleNumber,
            'RoleName' => $user->role?->RoleName,
        ];
    }
}
