<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionTemplateItemIngredient extends Model
{
    protected $fillable = ['option_template_item_id', 'ingredient_id', 'quantity'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function optionTemplateItem()
    {
        return $this->belongsTo(OptionTemplateItem::class);
    }
}