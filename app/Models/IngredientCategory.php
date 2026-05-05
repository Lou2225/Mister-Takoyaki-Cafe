<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'description',
        'sort_order'
    ];

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class, 'category_id');
    }

    /**
     * Get products that contain ingredients from this category
     */
    public function products()
    {
        return Product::whereHas('recipes', function ($query) {
            $query->whereHas('ingredient', function ($q) {
                $q->where('category_id', $this->id);
            });
        });
    }
}
