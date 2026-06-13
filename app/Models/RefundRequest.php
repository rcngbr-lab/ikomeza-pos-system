<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    public const STATUS_PENDING = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_EXECUTED = 'EXECUTED';

    protected $fillable = [
        'request_number',
        'sale_id',
        'requested_by',
        'approved_by',
        'executed_by',
        'amount',
        'reason',
        'status',
        'requested_at',
        'approved_at',
        'executed_at',
        'rejected_at',
        'approval_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}

