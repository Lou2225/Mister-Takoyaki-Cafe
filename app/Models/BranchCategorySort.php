<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchCategorySort extends Model
{
    protected $table = 'branch_category_sort';

    protected $fillable = [
        'branch_id',
        'category_id',
        'sort_order'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
