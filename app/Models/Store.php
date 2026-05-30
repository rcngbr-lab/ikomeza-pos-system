<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public const TYPE_MAIN = 'MAIN';
    public const TYPE_KITCHEN = 'KITCHEN';
    public const TYPE_BAR = 'BAR';

    protected $fillable = [
        'code',
        'name',
        'type',
        'department_id',
        'description',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function stocks()
    {
        return $this->hasMany(StoreStock::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'default_store_id');
    }
}
