<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_name',
        'branch_code',
        'delivery_fee',
        'distance_from_main',
        'address',
        'phone',
        'email',
        'user_id',
        'status',
        'is_main',
    ];

    /**
     * Get the manager of this branch.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the users/staff assigned to this branch.
     */
    public function staff()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    /**
     * All ingredient stock records for this branch.
     */
    public function ingredientStocks()
    {
        return $this->hasMany(BranchIngredientStock::class);
    }

    /**
     * Products explicitly assigned to this branch (scope = 'branch').
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'branch_product')->withTimestamps();
    }

    /**
     * All orders recorded in this branch.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Stock orders submitted by this branch.
     */
    public function stockOrdersRequested()
    {
        return $this->hasMany(StockOrder::class, 'requesting_branch_id');
    }

    /**
     * Stock orders fulfilled by this branch (as supplier/main branch).
     */
    public function stockOrdersAsSource()
    {
        return $this->hasMany(StockOrder::class, 'source_branch_id');
    }
}
