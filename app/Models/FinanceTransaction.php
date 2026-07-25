<?php

namespace App\Models;

use App\Support\TextNormalizer;
use Database\Factories\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'transaction_number',
    'transaction_date',
    'type',
    'account_id',
    'transfer_account_id',
    'category_id',
    'amount',
    'reference_number',
    'reference_type',
    'reference_id',
    'description',
    'status',
    'void_reason',
    'voided_at',
    'voided_by',
    'created_by',
    'updated_by',
])]
class FinanceTransaction extends Model
{
    /** @use HasFactory<FinanceTransactionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'amount' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            FinanceAccount::class,
            'account_id'
        );
    }

    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(
            FinanceAccount::class,
            'transfer_account_id'
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            FinanceCategory::class,
            'category_id'
        );
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    protected function referenceNumber(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => TextNormalizer::singleLine($value),
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => TextNormalizer::multiline($value),
        );
    }

    protected function voidReason(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => TextNormalizer::multiline($value),
        );
    }
}
