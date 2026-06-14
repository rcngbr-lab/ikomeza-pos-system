<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_BLOCKED = 'BLOCKED';
    public const STATUS_DEPLETED = 'DEPLETED';

    protected $fillable = [
        'product_id',
        'store_id',
        'supplier_id',
        'purchase_item_id',
        'branch_id',
        'batch_number',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'received_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'quantity_remaining' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'received_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
