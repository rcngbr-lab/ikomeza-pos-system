<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturn extends Model
{
    public const STATUS_PENDING = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'return_number',
        'return_type',
        'product_id',
        'from_store_id',
        'to_store_id',
        'supplier_id',
        'department_id',
        'quantity',
        'reason',
        'status',
        'recorded_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'approved_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
