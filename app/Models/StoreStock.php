<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreStock extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'department_id',
        'quantity',
        'alert_stock',
        'unit_cost',
        'total_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'alert_stock' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
