<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';

    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'tin',
        'address',
        'credit_limit',
        'balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }
}
