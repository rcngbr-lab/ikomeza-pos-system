<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    protected $fillable = [
        'recipe_id',
        'ingredient_product_id',
        'store_id',
        'quantity',
        'unit',
        'unit_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Product::class, 'ingredient_product_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
