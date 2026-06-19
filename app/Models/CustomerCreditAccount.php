<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditAccount extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BLOCKED = 'BLOCKED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'customer_id',
        'branch_id',
        'account_number',
        'category',
        'credit_limit',
        'credit_period_days',
        'risk_level',
        'status',
        'current_balance',
        'available_credit',
        'total_credit_sales',
        'total_payments',
        'total_outstanding',
        'last_payment_date',
        'last_credit_date',
        'blocked_reason',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'total_credit_sales' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'last_payment_date' => 'datetime',
        'last_credit_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(CreditTransaction::class, 'credit_account_id');
    }

    public function payments()
    {
        return $this->hasMany(CreditPayment::class, 'credit_account_id');
    }
}
