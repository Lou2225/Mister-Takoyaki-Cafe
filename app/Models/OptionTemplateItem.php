<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = ['template_id', 'name', 'price', 'is_default'];
    public function template()
    {
        return $this->belongsTo(OptionTemplate::class, 'template_id');
    }

    public function ingredients()
    {
        return $this->hasMany(OptionTemplateItemIngredient::class);
    }
}
