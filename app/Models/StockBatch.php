<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id',
        'source_type',
        'source_id',
        'qty_in',
        'qty_remaining',
        'cost_price',
        'batch_date',
        'created_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'batch_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleItemBatches()
    {
        return $this->hasMany(SaleItemBatch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'source_id')
            ->where('source_type', 'purchase');
    }
}