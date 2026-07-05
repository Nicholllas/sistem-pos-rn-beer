<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'qty',
        'cost_price',
        'subtotal',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class, 'source_id')
            ->where('source_type', 'purchase');
    }
}