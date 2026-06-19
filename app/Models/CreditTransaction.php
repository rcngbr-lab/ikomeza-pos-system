<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'customer_id',
        'credit_account_id',
        'branch_id',
        'sale_id',
        'approval_request_id',
        'transaction_type',
        'document_number',
        'description',
        'debit',
        'credit',
        'balance_before',
        'balance_after',
        'due_date',
        'transaction_date',
        'status',
        'posted_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'due_date' => 'date',
        'transaction_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditAccount()
    {
        return $this->belongsTo(CustomerCreditAccount::class, 'credit_account_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
