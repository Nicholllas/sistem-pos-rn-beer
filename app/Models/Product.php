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
        'packaging_type',
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
        return $this->belongsTo(Category::class)->withTrashed();
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

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function hasTransactionHistory(): bool
    {
        return $this->saleItems()->exists()
            || $this->purchaseItems()->exists()
            || $this->stockMovements()->exists()
            || $this->stockBatches()->exists();
    }
}
