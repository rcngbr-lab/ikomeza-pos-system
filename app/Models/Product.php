<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

        'tax_category',

        'expiry_alert_days',

        'image_path',

        'image_url',

    ];

    protected $casts = [

        'track_stock' => 'boolean',

        'active' => 'boolean',

        'buy_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

        'stock' => 'decimal:2',

        'alert_stock' => 'decimal:2',

        'expiry_alert_days' => 'integer',

    ];

    protected $appends = [
        'image_source',
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

    public function getImageSourceAttribute(): ?string
    {
        if (!empty($this->image_path)) {
            return Storage::disk('public')->url($this->image_path);
        }

        if (!empty($this->image_url)) {
            return $this->image_url;
        }

        return null;
    }
}
