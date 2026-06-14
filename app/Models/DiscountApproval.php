<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountApproval extends Model
{
    protected $fillable = [
        'sale_id',
        'requested_by',
        'approved_by',
        'branch_id',
        'subtotal',
        'discount_amount',
        'discount_percent',
        'status',
        'reason',
        'approved_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:3',
        'approved_at' => 'datetime',
    ];
}
