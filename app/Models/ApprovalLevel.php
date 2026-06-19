<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLevel extends Model
{
    protected $fillable = [
        'level_number',
        'name',
        'role_name',
        'min_amount',
        'max_amount',
        'active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'active' => 'boolean',
    ];
}
