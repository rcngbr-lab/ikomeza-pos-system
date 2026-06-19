<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'customer_id',
        'credit_account_id',
        'branch_id',
        'amount',
        'payment_method',
        'reference',
        'received_by',
        'received_at',
        'allocation_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditAccount()
    {
        return $this->belongsTo(CustomerCreditAccount::class, 'credit_account_id');
    }
}
