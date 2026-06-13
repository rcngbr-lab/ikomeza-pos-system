<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'ledger_account_id',
        'account_code',
        'account_name',
        'debit',
        'credit',
        'department_id',
        'branch_id',
        'memo',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];
}

