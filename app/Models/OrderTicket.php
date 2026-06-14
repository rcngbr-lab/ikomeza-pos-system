<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTicket extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PREPARING = 'PREPARING';
    public const STATUS_READY = 'READY';
    public const STATUS_SERVED = 'SERVED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const TYPE_KITCHEN = 'KOT';
    public const TYPE_BAR = 'BOT';

    protected $fillable = [
        'ticket_number',
        'sale_id',
        'department_id',
        'table_id',
        'created_by',
        'assigned_to',
        'ticket_type',
        'status',
        'sent_at',
        'accepted_at',
        'ready_at',
        'served_at',
        'notes',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderTicketItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
