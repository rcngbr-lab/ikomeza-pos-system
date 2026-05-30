<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'product_id',
        'department_id',
        'name',
        'yield_quantity',
        'notes',
        'active',
    ];

    protected $casts = [
        'yield_quantity' => 'decimal:3',
        'active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }
}
