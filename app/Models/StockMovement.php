<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'branch_id',
        'ingredient_id',
        'type',
        'quantity',
        'unit_cost',
        'reference_id',
        'expiry_date',
        'user_id',
        'remarks',
        'from_branch_id',
        'transfer_reference',
        'status',
        'batch_data'
    ];

    protected $casts = [
        'batch_data' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
