<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number',
        'entry_date',
        'source_type',
        'source_id',
        'reference',
        'description',
        'total_debit',
        'total_credit',
        'posted_by',
        'status',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }
}

