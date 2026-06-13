<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCount extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_APPROVED = 'APPROVED';

    protected $fillable = [
        'count_number',
        'store_id',
        'department_id',
        'branch_id',
        'counted_by',
        'approved_by',
        'status',
        'count_date',
        'submitted_at',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'count_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }
}

