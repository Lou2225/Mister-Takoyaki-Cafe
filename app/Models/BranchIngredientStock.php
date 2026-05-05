<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchIngredientStock extends Model
{
    use HasFactory;

    protected $table = 'branch_ingredient_stocks';

    protected $fillable = [
        'branch_id',
        'ingredient_id',
        'stock_quantity'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
