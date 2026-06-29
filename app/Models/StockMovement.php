<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'previous_stock',
        'new_stock',
        'note',
        'created_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}