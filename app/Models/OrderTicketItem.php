<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTicketItem extends Model
{
    protected $fillable = [
        'order_ticket_id',
        'sale_item_id',
        'product_id',
        'product_name',
        'quantity',
        'unit',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];
}

