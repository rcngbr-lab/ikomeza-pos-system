<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [

        'user_id',

        'branch_id',

        'shift_code',

        'opening_cash',

        'closing_cash',

        'expected_cash',

        'difference',

        'status',

        'is_open',

        'opened_at',

        'closed_at',

        'notes',

        'total_sales',
        'cash_sales',
        'momo_sales',
        'airtel_sales',
        'visa_sales',
        'mastercard_sales',
        'bank_transfer_sales',

        'locked_at',

        'locked_by',

    ];

    protected $casts = [

        'opening_cash' => 'decimal:2',

        'closing_cash' => 'decimal:2',

        'expected_cash' => 'decimal:2',

        'difference' => 'decimal:2',

        'total_sales' => 'decimal:2',

        'cash_sales' => 'decimal:2',

        'momo_sales' => 'decimal:2',

        'airtel_sales' => 'decimal:2',

        'visa_sales' => 'decimal:2',

        'mastercard_sales' => 'decimal:2',

        'bank_transfer_sales' => 'decimal:2',

        'is_open' => 'boolean',

        'opened_at' => 'datetime',

        'closed_at' => 'datetime',

        'locked_at' => 'datetime',

    ];

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function branch()
    {
        return $this->belongsTo(
            Branch::class
        );
    }

    public function sales()
    {
        return $this->hasMany(
            Sale::class
        );
    }

    public function getOpeningBalanceAttribute()
    {
        return $this->opening_cash;
    }

    public function getClosingBalanceAttribute()
    {
        return $this->closing_cash;
    }

    public function getDifferenceAttribute($value)
    {
        return $value ?? 0;
    }
}
