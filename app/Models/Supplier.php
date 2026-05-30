<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_number',
        'payment_terms',
        'supplied_categories',
        'department_id',
        'status',
        'notes',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
