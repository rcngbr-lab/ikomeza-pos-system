<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [

        'receipt_no',

        'branch_id',

        'user_id',

        'shift_id',

        'customer_name',

        'subtotal',

        'tax',

        'discount',

        'grand_total',

        'amount_paid',

        'change_amount',

        'payment_method',

        'payment_status',

        'sale_status',

        'notes',

        'is_refunded',

        'refund_amount',

        'refund_reason',

        'refunded_at',

        'refunded_by',

        'approved_by',

    ];

    protected $casts = [

        'subtotal' => 'decimal:2',

        'tax' => 'decimal:2',

        'discount' => 'decimal:2',

        'grand_total' => 'decimal:2',

        'amount_paid' => 'decimal:2',

        'change_amount' => 'decimal:2',

        'is_refunded' => 'boolean',

        'refunded_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | ITEMS
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            \App\Models\SaleItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CASHIER
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            \App\Models\User::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SHIFT
    |--------------------------------------------------------------------------
    */

    public function shift()
    {
        return $this->belongsTo(
            \App\Models\Shift::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BRANCH
    |--------------------------------------------------------------------------
    */

    public function branch()
    {
        return $this->belongsTo(
            \App\Models\Branch::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REFUNDS
    |--------------------------------------------------------------------------
    */

    public function refunds()
    {
        return $this->hasMany(
            \App\Models\Refund::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVER
    |--------------------------------------------------------------------------
    */

    public function approver()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'approved_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REFUND USER
    |--------------------------------------------------------------------------
    */

    public function refundUser()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'refunded_by'
        );
    }

/*
    |--------------------------------------------------------------------------
    | Payment Method
    |--------------------------------------------------------------------------
    */
public const PAYMENT_METHODS = [

    'CASH',

    'MOMO',

    'AIRTEL_MONEY',

    'VISA',

    'MASTER_CARD',

    'BANK_TRANSFER',

];

public const PAYMENT_METHOD_LABELS = [

    'CASH' => 'Cash',
    'MOMO' => 'MOMO',
    'AIRTEL_MONEY' => 'Airtel Money',
    'VISA' => 'VISA',
    'MASTER_CARD' => 'Mastercard',
    'BANK_TRANSFER' => 'Bank Transfer',

];

public static function normalizePaymentMethod(?string $method): string
{
    $method = strtoupper((string) $method);

    return match ($method) {
        'BANK', 'BANK_TRANSFER', 'TRANSFER' => 'BANK_TRANSFER',
        'MASTER', 'MASTERCARD', 'MASTER_CARD' => 'MASTER_CARD',
        'AIRTEL', 'AIRTEL_MONEY' => 'AIRTEL_MONEY',
        'MTN', 'MOMO', 'MOBILE_MONEY' => 'MOMO',
        'VISA' => 'VISA',
        default => 'CASH',
    };
}

public function paymentMethodLabel(): string
{
    return self::PAYMENT_METHOD_LABELS[
        self::normalizePaymentMethod($this->payment_method)
    ] ?? 'Cash';
}




}
