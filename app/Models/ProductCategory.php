<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'description',
        'production_station',
        'sort_order'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function branchSorts()
    {
        return $this->hasMany(BranchCategorySort::class, 'category_id');
    }
}
