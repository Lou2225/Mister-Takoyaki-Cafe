<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'house_no',
        'street',
        'barangay',
        'city',
        'province',
        'note',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}