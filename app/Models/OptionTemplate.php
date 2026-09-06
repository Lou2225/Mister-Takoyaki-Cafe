<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionTemplate extends Model
{
    use HasFactory;

        protected $fillable = ['name', 'price_mode', 'is_required', 'no_recipe_required'];

    protected $casts = [
        'is_required' => 'boolean',
        'no_recipe_required' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(OptionTemplateItem::class, 'template_id');
    }
}
