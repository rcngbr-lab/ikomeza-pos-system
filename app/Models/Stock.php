<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
use App\Models\User;

class Stock extends Model
{
    protected $fillable = [

        'product_id',

        'department_id',

        'type',

        'quantity',

        'before_stock',

        'after_stock',

        'note',

        'user_id'

    ];

    /*
    |--------------------------------------------------------------------------
    | PRODUCT RELATION
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER RELATION
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }
}
