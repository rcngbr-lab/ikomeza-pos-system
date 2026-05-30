<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreIssueItem extends Model
{
    protected $fillable = [
        'store_issue_id',
        'product_id',
        'quantity_requested',
        'quantity_issued',
        'quantity_received',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'quantity_issued' => 'decimal:3',
        'quantity_received' => 'decimal:3',
    ];

    public function issue()
    {
        return $this->belongsTo(StoreIssue::class, 'store_issue_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
