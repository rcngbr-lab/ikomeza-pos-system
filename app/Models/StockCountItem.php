<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model
{
    protected $fillable = [
        'stock_count_id',
        'product_id',
        'branch_id',
        'barcode',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'variance_value',
        'reason',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'variance_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'variance_value' => 'decimal:2',
    ];
}
