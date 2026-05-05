<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    protected $fillable = ['name', 'price', 'is_active'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'modifier_product');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'modifier_id');
    }
}
