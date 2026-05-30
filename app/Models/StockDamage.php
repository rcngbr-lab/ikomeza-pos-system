<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockDamage extends Model
{
    public const STATUS_PENDING = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'damage_number',
        'product_id',
        'store_id',
        'department_id',
        'quantity',
        'reason',
        'notes',
        'recorded_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'approved_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
