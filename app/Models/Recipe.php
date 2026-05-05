<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'product_id',
        'ingredient_id',
        'quantity',
        'product_option_id',
        'modifier_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function modifier()
    {
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}

