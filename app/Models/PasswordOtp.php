<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    public const PURPOSE_FORGOT_PASSWORD = 'forgot_password';
    public const PURPOSE_GOOGLE_SIGNIN = 'google_signin';
    public const MAX_ATTEMPTS = 5;
    public const OTP_EXPIRATION_MINUTES = 10;

    protected $fillable = [
        'email',
        'otp',
        'purpose',
        'challenge_id',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function isExpired(): bool
    {
        return !$this->expires_at || now()->greaterThanOrEqualTo($this->expires_at);
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }
}