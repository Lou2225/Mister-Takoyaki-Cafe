<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    protected $fillable = ['email', 'otp', 'attempts', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= 5;
    }
}
