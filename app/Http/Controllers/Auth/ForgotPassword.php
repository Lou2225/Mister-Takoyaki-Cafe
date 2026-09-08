<?php

namespace App\Http\Controllers\Auth;

use App\Mail\OtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{
    // Steps: 1 = email, 2 = otp, 3 = new password
    public int     $step         = 1;
    public string  $email        = '';
    public string  $otp          = '';
    public string  $password     = '';
    public string  $passwordConfirmation = '';
    public ?string $challengeId  = null;

    // Countdown (seconds remaining shown in UI)
    public int $resendCooldown = 0;

    // Step 1: Send OTP
    public function sendOtp(): void
    {
        $this->email = trim(strtolower($this->email));

        $this->validate([
            'email' => ['required', 'email:rfc', 'max:255', 'exists:users,email'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email'    => 'Please enter a valid email address.',
            'email.exists'   => 'We could not find an account registered with that email address.',
        ]);

        // Always delete any existing forgot password OTP for this email
        PasswordOtp::where('email', $this->email)
            ->where('purpose', PasswordOtp::PURPOSE_FORGOT_PASSWORD)
            ->delete();

        // Generate a cryptographically secure 6-digit code and unique challenge id
        $plainOtp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->challengeId = (string) Str::uuid();

        PasswordOtp::create([
            'email'        => $this->email,
            'otp'          => Hash::make($plainOtp),
            'purpose'      => PasswordOtp::PURPOSE_FORGOT_PASSWORD,
            'challenge_id' => $this->challengeId,
            'attempts'     => 0,
            'expires_at'   => now()->addMinutes(PasswordOtp::OTP_EXPIRATION_MINUTES),
        ]);

        // Attempt to send the email — fail silently to prevent enumeration
        $user = User::where('email', $this->email)->first();
        if ($user) {
            try {
                Mail::to($this->email)->send(new OtpMail(
                    $plainOtp,
                    $user->first_name ?: ($user->name ?: 'User'),
                    PasswordOtp::PURPOSE_FORGOT_PASSWORD
                ));
            } catch (\Exception $e) {
                Log::error('OTP mail failed: ' . $e->getMessage());
            }
        }

        // Always advance & show the same message (no email enumeration)
        $this->step = 2;
        $this->otp  = '';
        $this->resendCooldown = 60;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Verification code sent to your email!']);
    }

    // Step 2: Verify OTP
    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'otp.required' => 'Please enter the 6-digit code.',
            'otp.size'     => 'The code must be exactly 6 digits.',
            'otp.regex'    => 'The code must consist of numbers only.',
        ]);

        $record = null;
        if ($this->challengeId) {
            $record = PasswordOtp::where('email', $this->email)
                ->where('purpose', PasswordOtp::PURPOSE_FORGOT_PASSWORD)
                ->where('challenge_id', $this->challengeId)
                ->first();
        }

        if (! $record) {
            $record = PasswordOtp::where('email', $this->email)
                ->where('purpose', PasswordOtp::PURPOSE_FORGOT_PASSWORD)
                ->latest()
                ->first();
        }

        if (! $record) {
            $this->addError('otp', 'No active code found. Please request a new one.');
            return;
        }

        if ($record->isExpired()) {
            $record->delete();
            $this->addError('otp', 'This code has expired. Please request a new one.');
            $this->step = 1;
            return;
        }

        if ($record->isExhausted()) {
            $record->delete();
            $this->addError('otp', 'Too many incorrect attempts. Please request a new code.');
            $this->step = 1;
            return;
        }

        if (! Hash::check($this->otp, $record->otp)) {
            $record->increment('attempts');
            $remaining = 5 - $record->fresh()->attempts;
            $this->addError('otp', "Incorrect code. {$remaining} attempt(s) remaining.");
            return;
        }

        // OTP is valid — store a server-side session token and advance
        session(['otp_verified_email' => $this->email, 'otp_verified_at' => now()->timestamp]);
        $record->delete(); // One-time use — delete immediately
        $this->challengeId = null;
        $this->step = 3;
        $this->otp  = '';
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Code verified! You may now set a new password.']);
    }

    // Step 3: Reset Password
    public function resetPassword()
    {
        // Guard: ensure the session token is valid and recent (max 15 min)
        $verifiedEmail = session('otp_verified_email');
        $verifiedAt    = session('otp_verified_at');

        if (
            ! $verifiedEmail ||
            $verifiedEmail !== $this->email ||
            ! $verifiedAt ||
            now()->timestamp - $verifiedAt > 900
        ) {
            session()->forget(['otp_verified_email', 'otp_verified_at']);
            $this->step = 1;
            $this->addError('password', 'Your session expired. Please start over.');
            return;
        }

        $this->password              = $this->password ?? '';
        $this->passwordConfirmation  = $this->passwordConfirmation ?? '';

        $this->validate([
            'password' => [
                'required', 'string', 'min:8', 'max:128',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'same:passwordConfirmation',
            ],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.regex'    => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.same'     => 'Password confirmation does not match.',
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->step = 1;
            return;
        }

        $user->password = Hash::make($this->password);
        $user->save();

        // Clean up session
        session()->forget(['otp_verified_email', 'otp_verified_at']);

        session()->flash('success', 'Your password has been reset successfully. Please log in.');
        return redirect()->route('login');
    }

    // Resend OTP
    public function resendOtp()
    {
        $this->step = 1;
        $this->otp  = '';
        $this->challengeId = null;
        $this->sendOtp();
    }

    public function render()
    {
        return view('auth.forgot-password')->layout('layouts.auth');
    }
}
