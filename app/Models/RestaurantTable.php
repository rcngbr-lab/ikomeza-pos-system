<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_OCCUPIED = 'OCCUPIED';

    protected $fillable = [
        'branch_id',
        'table_code',
        'name',
        'section',
        'seats',
        'status',
        'assigned_user_id',
        'notes',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'table_id');
    }
}

