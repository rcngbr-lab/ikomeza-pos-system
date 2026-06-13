<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_ORDERED = 'ORDERED';
    public const STATUS_PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const PAYMENT_UNPAID = 'UNPAID';
    public const PAYMENT_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const PAYMENT_PAID = 'PAID';
    public const PAYMENT_CREDIT = 'CREDIT';

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'requisition_id',
        'department_id',
        'store_id',
        'purchased_by',
        'approved_by',
        'received_by',
        'invoice_number',
        'purchase_date',
        'expected_delivery_date',
        'approved_at',
        'received_date',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'payment_status',
        'accounting_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'received_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_ORDERED,
            self::STATUS_PARTIALLY_RECEIVED,
        ], true);
    }
}
