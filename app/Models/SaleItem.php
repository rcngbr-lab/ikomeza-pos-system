<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [

        'sale_id',

        'product_id',

        'quantity',

        'price',

        'unit_price',

        'cost_price',

        'discount',

        'tax',

        'subtotal',

        'total',

        'profit',

        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function sale()
    {
        return $this->belongsTo(
            Sale::class
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }
}
