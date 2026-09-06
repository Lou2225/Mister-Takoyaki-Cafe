<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


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
            'phone'      => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

       $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'address' => $request->address,
            'phone'      => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => 4,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please log in.',
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
            ],
        ], 201);
    }
    
      /**
     * Register a new customer via Google Sign-In (no password required).
     * Mirrors the same dual-verification pattern as googleCheck/googleLogin:
     * verifies via id_token when available, falls back to email otherwise.
     */
    
    public function googleRegister(Request $request)
    {
        $validated = $request->validate([
            'id_token'   => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
        ]);
    
        // The email must come only from the verified Google ID token.
        $googleEmail = $this->verifyGoogleToken($validated['id_token']);
    
        if (!$googleEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unverified Google account.',
            ], 401);
        }
    
        if (User::where('email', $googleEmail)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this Google email already exists. Please log in instead.',
            ], 409);
        }
    
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $googleEmail,
            'phone'      => $validated['phone'] ?? null,
            'password'   => Hash::make(Str::random(32)),
            'role_id'    => 4,
            'is_active'  => true,
        ]);
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => [
                'user' => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'address'    => $user->address,
                    'phone'      => $user->phone,
                    'role_id'    => $user->role_id,
                    'role'       => $this->getRoleName($user->role_id),
                    'branch_id'  => $user->branch_id,
                    'is_active'  => $user->is_active,
                ],
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }




    /**
     * Check Google account existence via verified Google ID Token or email
     */
    
    public function googleCheck(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string',
        ]);
    
        $googleEmail = $this->verifyGoogleToken($validated['id_token']);
    
        if (!$googleEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unverified Google account.',
            ], 401);
        }
    
        $user = User::where('email', $googleEmail)->first();
    
        if ($user && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated.',
            ], 403);
        }
    
        return response()->json([
            'success' => true,
            'account_exists' => $user !== null,
            'email' => $googleEmail,
        ], 200);
    }

    /**
     * Authenticate existing user after Google ID Token & OTP verification
     */
    
    public function googleLogin(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string',
            'email' => 'nullable|email',
            'otp_verification_token' => 'required|string',
        ]);
    
        $googleEmail = $this->verifyGoogleToken($validated['id_token']);
    
        if (!$googleEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unverified Google account.',
            ], 401);
        }
    
        $tokenKey = 'otp_verified:' . hash(
            'sha256',
            $validated['otp_verification_token']
        );
    
        $verification = Cache::get($tokenKey);
    
        if (
            !$verification ||
            ($verification['purpose'] ?? null) !== 'google_signin' ||
            strtolower($verification['email'] ?? '') !== $googleEmail
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Google sign-in verification has expired or is invalid. Please request a new OTP.',
            ], 401);
        }
    
        $user = User::where('email', $googleEmail)->first();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found associated with this email.',
            ], 404);
        }
    
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated.',
            ], 403);
        }
    
        // Atomically consume the temporary verification token.
        $consumedVerification = Cache::pull($tokenKey);
    
        if (
            !$consumedVerification ||
            ($consumedVerification['purpose'] ?? null) !== 'google_signin' ||
            strtolower($consumedVerification['email'] ?? '') !== $googleEmail
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Google sign-in verification has expired or is invalid. Please request a new OTP.',
            ], 401);
        }
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'success' => true,
            'message' => 'Google login successful',
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
     * Helper to verify Google ID Token with Google OAuth tokeninfo API
     */
   
    private function verifyGoogleToken(?string $idToken): ?string
    {
        if (!$idToken) {
            return null;
        }
    
        try {
            $response = Http::timeout(10)->get(
                'https://oauth2.googleapis.com/tokeninfo',
                ['id_token' => $idToken]
            );
    
            if (!$response->successful()) {
                return null;
            }
    
            $data = $response->json();
            $configuredClientId = (string) config('services.google.client_id');
    
            $issuerValid = in_array(
                $data['iss'] ?? null,
                [
                    'https://accounts.google.com',
                    'accounts.google.com',
                ],
                true
            );
    
            $audienceValid = $configuredClientId !== ''
                && ($data['aud'] ?? null) === $configuredClientId;
    
            $emailVerified = in_array(
                $data['email_verified'] ?? null,
                [true, 'true'],
                true
            );
    
            if (
                empty($data['email']) ||
                !$emailVerified ||
                !$issuerValid ||
                !$audienceValid ||
                empty($data['exp']) ||
                (int) $data['exp'] <= now()->timestamp
            ) {
                return null;
            }
    
            return strtolower(trim($data['email']));
        } catch (\Throwable) {
            return null;
        }
    }
    
    

    /**
     * Check if email already exists
     */
    public function checkEmailExists(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        $exists = User::where('email', $request->email)->exists();
    
        return response()->json([
            'exists' => $exists,
        ], 200);
    }
    
    
    
    /**
     * Reset user password (unauthenticated)
     */
   
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'otp_verification_token' => 'required|string',
        ]);
    
        $email = strtolower(trim($validated['email']));
    
        $tokenKey = 'otp_verified:' . hash(
            'sha256',
            $validated['otp_verification_token']
        );
    
        $verification = Cache::get($tokenKey);
    
        if (
            !$verification ||
            ($verification['purpose'] ?? null) !== 'forgot_password' ||
            strtolower($verification['email'] ?? '') !== $email
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset verification has expired or is invalid. Please request a new OTP.',
            ], 401);
        }
    
        $user = User::where('email', $email)->first();
    
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }
    
        // Atomically consume the token before changing the password.
        $consumedVerification = Cache::pull($tokenKey);
    
        if (
            !$consumedVerification ||
            ($consumedVerification['purpose'] ?? null) !== 'forgot_password' ||
            strtolower($consumedVerification['email'] ?? '') !== $email
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset verification has expired or is invalid. Please request a new OTP.',
            ], 401);
        }
    
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
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
    
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        $user = $request->user();
    
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }
    
        $user->update([
            'password' => Hash::make($request->password),
        ]);
    
        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
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