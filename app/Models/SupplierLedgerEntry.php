<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierLedgerEntry extends Model
{
    protected $fillable = [
        'supplier_id',
        'purchase_id',
        'entry_type',
        'debit',
        'credit',
        'balance_after',
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

