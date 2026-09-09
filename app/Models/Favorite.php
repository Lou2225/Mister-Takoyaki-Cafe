<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Favorite extends Model
{
    use HasFactory;
    protected $table = 'favorites';
    protected $fillable = [
        'user_id',
        'product_id',
    ];
    /**
     * The customer who favorited the product.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /**
     * The favorited product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}