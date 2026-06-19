<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'request_number',
        'approval_type',
        'module',
        'reference_type',
        'reference_id',
        'customer_id',
        'credit_account_id',
        'branch_id',
        'requested_by',
        'approved_by',
        'level_required',
        'amount',
        'status',
        'reason',
        'approval_note',
        'requested_at',
        'approved_at',
        'rejected_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
