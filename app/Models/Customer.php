<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BLOCKED = 'BLOCKED';
    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'customer_code',
        'branch_id',
        'name',
        'category',
        'phone',
        'email',
        'national_id',
        'tin',
        'company_registration_number',
        'address',
        'credit_limit',
        'credit_period_days',
        'risk_level',
        'balance',
        'total_credit_sales',
        'total_payments',
        'total_outstanding',
        'last_payment_date',
        'last_credit_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'credit_period_days' => 'integer',
        'balance' => 'decimal:2',
        'total_credit_sales' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'last_payment_date' => 'datetime',
        'last_credit_date' => 'datetime',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }

    public function creditAccount()
    {
        return $this->hasOne(CustomerCreditAccount::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function creditPayments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function availableCredit(): float
    {
        $limit = (float) ($this->creditAccount?->credit_limit ?? $this->credit_limit ?? 0);
        $balance = (float) ($this->creditAccount?->current_balance ?? $this->balance ?? 0);

        return max($limit - $balance, 0);
    }
}
