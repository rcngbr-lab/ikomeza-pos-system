<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNABLE
    |--------------------------------------------------------------------------
    */

   protected $fillable = [

    'user_id',

    'role_name',

    'department_id',

    'branch_id',

    'event_type',

    'event',

    'action',

    'module',

    'model',

    'model_type',

    'model_id',

    'reference',

    'description',

    'old_values',

    'new_values',

    'metadata',

    'amount',

    'quantity_before',

    'quantity_changed',

    'quantity_after',

    'severity',

    'ip_address',

    'user_agent',

    'device',

];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_changed' => 'decimal:2',
        'quantity_after' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function branch()
    {
        return $this->belongsTo(
            Branch::class
        );
    }

    public function displayAction(): string
    {
        return $this->action ?: $this->event ?: 'ACTIVITY';
    }

    public function displayModule(): string
    {
        return $this->module ?: $this->model ?: 'System';
    }

    public function displayReference(): string
    {
        return $this->reference ?: ($this->model_id ? '#' . $this->model_id : '-');
    }
}
