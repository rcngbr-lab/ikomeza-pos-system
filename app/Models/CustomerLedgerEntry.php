<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerLedgerEntry extends Model
{
    protected $fillable = [
        'customer_id',
        'sale_id',
        'entry_type',
        'debit',
        'credit',
        'balance_after',
        'payment_method',
        'reference',
        'description',
        'created_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];
}

