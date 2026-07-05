<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItemBatch extends Model
{
    protected $fillable = [
        'sale_item_id',
        'stock_batch_id',
        'qty',
        'cost_price',
        'subtotal_cost',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'subtotal_cost' => 'decimal:2',
    ];

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}