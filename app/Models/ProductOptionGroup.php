<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionGroup extends Model
{
        protected $fillable = [
        'product_id',
        'name',
        'price_mode',
        'max_select',
        'is_required',
        'no_recipe_required',
        'sort_order'
    ];

    protected $casts = [
        'max_select' => 'integer',
        'is_required' => 'boolean',
        'no_recipe_required' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class, 'group_id')->orderBy('sort_order');
    }
}
