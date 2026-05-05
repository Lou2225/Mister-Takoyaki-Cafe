<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price_mode', 'is_required'];

    public function items()
    {
        return $this->hasMany(OptionTemplateItem::class, 'template_id');
    }
}
