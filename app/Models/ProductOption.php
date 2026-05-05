<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $fillable = [
        'group_id',
        'name',
        'price',
        'cost',
        'is_default',
        'sort_order'
    ];

    public function group()
    {
        return $this->belongsTo(ProductOptionGroup::class, 'group_id');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'product_option_id');
    }
}
