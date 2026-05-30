<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [

        'product_id',
        'department_id',
        'branch_id',
        'user_id',
        'from_store_id',
        'to_store_id',
        'type',
        'movement_type',
        'quantity',
        'before_stock',
        'after_stock',
        'quantity_before',
        'quantity_changed',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'performed_by',
        'approved_by',
        'reference_type',
        'reference_id',
        'reason',
        'notes',

    ];

    protected $casts = [
        'quantity_before' => 'decimal:3',
        'quantity_changed' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | PRODUCT
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function fromStore()
    {
        return $this->belongsTo(
            Store::class,
            'from_store_id'
        );
    }

    public function toStore()
    {
        return $this->belongsTo(
            Store::class,
            'to_store_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function performer()
    {
        return $this->belongsTo(
            User::class,
            'performed_by'
        );
    }

    public function approver()
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BRANCH
    |--------------------------------------------------------------------------
    */

    public function branch()
    {
        return $this->belongsTo(
            Branch::class
        );
    }
}
