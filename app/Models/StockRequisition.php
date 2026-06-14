<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockRequisition extends Model
{
    public const TYPE_STOCK_IN = 'STOCK_IN';
    public const TYPE_DAMAGED = 'DAMAGED';
    public const TYPE_STOCK_OUT = 'STOCK_OUT';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CONVERTED_TO_PURCHASE = 'CONVERTED_TO_PURCHASE';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_PROCESSED = 'PROCESSED';

    protected $fillable = [
        'product_id',
        'branch_id',
        'department_id',
        'requester_id',
        'approver_id',
        'type',
        'quantity',
        'status',
        'reason',
        'manager_note',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_STOCK_IN => 'Stock In',
            self::TYPE_DAMAGED => 'Damaged Stock',
            self::TYPE_STOCK_OUT => 'Stock Out',
            default => str($this->type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function statusLabel(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
