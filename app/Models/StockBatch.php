<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'branch_id',
        'batch_number',
        'current_quantity',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

}
