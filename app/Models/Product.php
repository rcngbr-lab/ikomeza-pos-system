<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [

        'product_code',

        'barcode',

        'name',

        'description',

        'category_id',

        'department_id',

        'default_store_id',

        'supplier_id',

        'product_type',

        'buy_price',

        'selling_price',

        'track_stock',

        'stock',

        'alert_stock',

        'unit',

        'active',

        'status',

    ];

    protected $casts = [

        'track_stock' => 'boolean',

        'active' => 'boolean',

        'buy_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

        'stock' => 'decimal:2',

        'alert_stock' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | CATEGORY RELATION
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(
            Category::class
        );
    }

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function defaultStore()
    {
        return $this->belongsTo(
            Store::class,
            'default_store_id'
        );
    }

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
        );
    }

    public function storeStocks()
    {
        return $this->hasMany(
            StoreStock::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SALE ITEMS RELATION
    |--------------------------------------------------------------------------
    */

    public function saleItems()
    {
        return $this->hasMany(
            SaleItem::class
        );
    }



public function stockMovements()
{
    return $this->hasMany(
        StockMovement::class
    );
}





}
