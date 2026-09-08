<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::creating(function (PasswordOtp $otp) {
            if (empty($otp->challenge_id)) {
                $otp->challenge_id = (string) Str::uuid();
            }
            if (empty($otp->purpose)) {
                $otp->purpose = self::PURPOSE_FORGOT_PASSWORD;
            }
        });
    }

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