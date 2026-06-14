<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [
        'sale_id',
        'shift_id',
        'customer_id',
        'branch_id',
        'received_by',
        'method',
        'amount',
        'change_amount',
        'reference',
        'provider_name',
        'payment_reference',
        'transaction_id',
        'payment_status',
        'reconciliation_status',
        'reconciled_by',
        'reconciled_at',
        'reconciliation_notes',
        'idempotency_key',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
