<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCollection extends Model
{
    protected $fillable = [
        'collection_number',
        'customer_id',
        'credit_account_id',
        'branch_id',
        'stage',
        'channel',
        'contact_person',
        'commitment_amount',
        'commitment_date',
        'next_follow_up_at',
        'status',
        'notes',
        'handled_by',
    ];

    protected $casts = [
        'commitment_amount' => 'decimal:2',
        'commitment_date' => 'date',
        'next_follow_up_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
