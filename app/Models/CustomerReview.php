<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'order_id',
        'device_id',
        'device_fingerprint',
        'answers',
        'customer_name',
        'contact_number',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
