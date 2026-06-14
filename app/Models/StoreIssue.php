<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreIssue extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'issue_number',
        'branch_id',
        'from_store_id',
        'to_store_id',
        'department_id',
        'requisition_id',
        'issued_by',
        'received_by',
        'approved_by',
        'issue_date',
        'received_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'received_date' => 'datetime',
    ];

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function items()
    {
        return $this->hasMany(StoreIssueItem::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
