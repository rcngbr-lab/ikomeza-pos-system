<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_COMPLETED = 'COMPLETED';

    protected $fillable = [

        'sale_id',

        'user_id',

        'amount',

        'reason',

        'status',

        'refunded_at',

    ];

    protected $casts = [

        'amount' => 'decimal:2',

        'refunded_at' => 'datetime',

    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
