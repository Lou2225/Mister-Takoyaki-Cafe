<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    private const VERIFICATION_TOKEN_TTL = 600;

    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email',
            'purpose' => 'required|in:forgot_password,google_signin',
            'id_token' => 'nullable|string',
        ]);

        $purpose = $validated['purpose'];

        if ($purpose === PasswordOtp::PURPOSE_GOOGLE_SIGNIN) {
            if (empty($validated['id_token'])) {
                throw ValidationException::withMessages([
                    'id_token' => ['A valid Google ID token is required.'],
                ]);
            }

            $email = $this->verifiedGoogleEmail($validated['id_token']);

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or unverified Google account.',
                ], 401);
            }
        } else {
            if (empty($validated['email'])) {
                throw ValidationException::withMessages([
                    'email' => ['The email field is required.'],
                ]);
            }

            $email = strtolower(trim($validated['email']));
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated.',
            ], 403);
        }

        $this->invalidateCurrentChallenge($email, $purpose);

        $plainOtp = (string) random_int(100000, 999999);
        $challengeId = (string) Str::uuid();
        $expiresAt = now()->addMinutes(PasswordOtp::OTP_EXPIRATION_MINUTES);

        PasswordOtp::create([
            'email' => $email,
            'otp' => Hash::make($plainOtp),
            'purpose' => $purpose,
            'challenge_id' => $challengeId,
            'attempts' => 0,
            'expires_at' => $expiresAt,
        ]);

        Cache::put(
            $this->currentChallengeKey($email, $purpose),
            $challengeId,
            $expiresAt
        );

        Mail::to($email)->send(new OtpMail(
            $plainOtp,
            $user->name ?: 'User',
            $purpose
        ));

        return response()->json([
            'success' => true,
            'message' => $purpose === PasswordOtp::PURPOSE_GOOGLE_SIGNIN
                ? 'A Google sign-in verification code has been sent to your email.'
                : 'A password reset code has been sent to your email.',
            'data' => [
                'challenge_id' => $challengeId,
                'purpose' => $purpose,
                'expires_in' => PasswordOtp::OTP_EXPIRATION_MINUTES * 60,
            ],
        ], 200);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'purpose' => 'required|in:forgot_password,google_signin',
            'challenge_id' => 'required|uuid',
        ]);

        $email = strtolower(trim($validated['email']));
        $purpose = $validated['purpose'];
        $challengeId = $validated['challenge_id'];
        $verificationToken = null;
        $verificationData = null;
        $invalidateChallenge = false;

        DB::transaction(function () use (
            $email,
            $purpose,
            $challengeId,
            $validated,
            &$verificationToken,
            &$verificationData,
            &$invalidateChallenge
        ) {
            $currentChallenge = Cache::get(
                $this->currentChallengeKey($email, $purpose)
            );

            if (!hash_equals((string) $challengeId, (string) $currentChallenge)) {
                throw ValidationException::withMessages([
                    'challenge_id' => ['This verification code is no longer valid. Please request a new one.'],
                ]);
            }

            $otpRecord = PasswordOtp::where('email', $email)
                ->where('purpose', $purpose)
                ->where('challenge_id', $challengeId)
                ->lockForUpdate()
                ->first();

            if (!$otpRecord) {
                throw ValidationException::withMessages([
                    'challenge_id' => ['This verification code is no longer valid. Please request a new one.'],
                ]);
            }

            if ($otpRecord->isExpired()) {
                $otpRecord->delete();
                $invalidateChallenge = true;

                throw ValidationException::withMessages([
                    'otp' => ['This verification code has expired. Please request a new one.'],
                ]);
            }

            if ($otpRecord->isExhausted()) {
                $otpRecord->delete();
                $invalidateChallenge = true;

                throw ValidationException::withMessages([
                    'otp' => ['Too many incorrect attempts. Please request a new code.'],
                ]);
            }

            if (!Hash::check($validated['otp'], $otpRecord->otp)) {
                $otpRecord->increment('attempts');

                if ($otpRecord->attempts >= PasswordOtp::MAX_ATTEMPTS) {
                    $otpRecord->delete();
                    $invalidateChallenge = true;

                    throw ValidationException::withMessages([
                        'otp' => ['Too many incorrect attempts. Please request a new code.'],
                    ]);
                }

                throw ValidationException::withMessages([
                    'otp' => ['Invalid verification code.'],
                ]);
            }

            $verificationToken = Str::random(64);
            $verificationData = [
                'email' => $email,
                'purpose' => $purpose,
                'challenge_id' => $challengeId,
                'verified_at' => now()->toIso8601String(),
            ];

            $otpRecord->delete();
            $invalidateChallenge = true;
        });

        if ($invalidateChallenge) {
            Cache::forget($this->currentChallengeKey($email, $purpose));
        }

        Cache::put(
            $this->verificationTokenKey($verificationToken),
            $verificationData,
            self::VERIFICATION_TOKEN_TTL
        );

        return response()->json([
            'success' => true,
            'message' => 'Verification successful.',
            'data' => [
                'verification_token' => $verificationToken,
                'purpose' => $purpose,
                'email' => $email,
            ],
        ], 200);
    }

    private function invalidateCurrentChallenge(string $email, string $purpose): void
    {
        $key = $this->currentChallengeKey($email, $purpose);
        $challengeId = Cache::pull($key);

        if ($challengeId) {
            PasswordOtp::where('email', $email)
                ->where('purpose', $purpose)
                ->where('challenge_id', $challengeId)
                ->delete();
        }

        PasswordOtp::where('email', $email)
            ->where('purpose', $purpose)
            ->delete();
    }

    private function currentChallengeKey(string $email, string $purpose): string
    {
        return 'otp_current:' . hash('sha256', $email . '|' . $purpose);
    }

    private function verificationTokenKey(string $token): string
    {
        return 'otp_verified:' . hash('sha256', $token);
    }

    private function verifiedGoogleEmail(string $idToken): ?string
    {
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

            if (
                empty($data['email']) ||
                !in_array($data['email_verified'] ?? null, [true, 'true'], true) ||
                ($data['iss'] ?? null) !== 'https://accounts.google.com' ||
                empty($data['exp']) ||
                (int) $data['exp'] <= now()->timestamp ||
                ($configuredClientId !== '' &&
                    ($data['aud'] ?? null) !== $configuredClientId)
            ) {
                return null;
            }

            return strtolower(trim($data['email']));
        } catch (\Throwable) {
            return null;
        }
    }
}