<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
    'category_id',
    'brand',
    'variant',
    'size',
    'unit',
    'name',
    'sku',
    'barcode',
    'cost_price',
    'price',
    'stock',
    'minimum_stock',
    'image',
    'is_active',
    'created_by',
    'updated_by',
    'last_stock_updated_by',
    'last_stock_updated_at',
    ];

    protected $casts = [
    'cost_price' => 'decimal:2',
    'price' => 'decimal:2',
    'stock' => 'integer',
    'minimum_stock' => 'integer',
    'size' => 'integer',
    'is_active' => 'boolean',
    'last_stock_updated_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lastStockUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_stock_updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'minimum_stock');
    }
}