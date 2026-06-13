<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [

        'sale_id',

        'product_id',

        'product_name',

        'product_code',

        'department_id',

        'department_name',

        'quantity',

        'price',

        'unit_price',

        'cost_price',

        'discount',

        'tax',

        'taxable_amount',

        'vat_rate',

        'vat_amount',

        'subtotal',

        'total',

        'profit',

        'status',

        'ticket_status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'vat_rate' => 'decimal:3',
        'vat_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'profit' => 'decimal:2',
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

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }
}
