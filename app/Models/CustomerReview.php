<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'answers',
        'customer_name',
        'contact_number',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
