<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and return API token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        // Generate API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'address' => $user->address,
                    'phone' => $user->phone,
                    'role_id' => $user->role_id,
                    'role' => $this->getRoleName($user->role_id),
                    'branch_id' => $user->branch_id,
                    'is_active' => $user->is_active,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Register a new customer user
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,NULL,id,role_id,4',
            'address' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'address' => $request->address,
            'password' => Hash::make($request->password),
            'role_id' => 4, // Customer role
            'is_active' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'address' => $user->address,
                    'phone' => $user->phone,
                    'role_id' => $user->role_id,
                    'role' => $this->getRoleName($user->role_id),
                    'is_active' => $user->is_active,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Check if email already exists
     */
    public function checkEmailExists(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = User::where('email', $request->email)
            ->where('role_id', 4)
            ->exists();

        return response()->json([
            'exists' => $exists,
        ], 200);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'email' => $request->user()->email,
                    'address' => $request->user()->address,
                    'phone' => $request->user()->phone,
                    'role_id' => $request->user()->role_id,
                    'role' => $this->getRoleName($request->user()->role_id),
                    'branch_id' => $request->user()->branch_id,
                    'is_active' => $request->user()->is_active,
                ],
            ],
        ], 200);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get role name from role_id
     */
    private function getRoleName(int $roleId): string
    {
        return match($roleId) {
            1 => 'super_admin',
            2 => 'admin',
            3 => 'staff',
            4 => 'customer',
            5 => 'rider',
            default => 'unknown',
        };
    }
}