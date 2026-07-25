<?php

namespace App\Models;

use App\Support\TextNormalizer;
use Database\Factories\FinanceAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'name',
    'type',
    'opening_balance',
    'is_active',
    'created_by',
    'updated_by',
])]
class FinanceAccount extends Model
{
    /** @use HasFactory<FinanceAccountFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            FinanceTransaction::class,
            'account_id'
        );
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(
            FinanceTransaction::class,
            'transfer_account_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCurrentBalanceAttribute(): float
    {
        $income = $this->transactions()
            ->where('status', 'posted')
            ->where('type', 'income')
            ->sum('amount');

        $expense = $this->transactions()
            ->where('status', 'posted')
            ->where('type', 'expense')
            ->sum('amount');

        $outgoingTransfer = $this->transactions()
            ->where('status', 'posted')
            ->where('type', 'transfer')
            ->sum('amount');

        $incomingTransfer = $this->incomingTransfers()
            ->where('status', 'posted')
            ->where('type', 'transfer')
            ->sum('amount');

        return (float) $this->opening_balance
            + (float) $income
            + (float) $incomingTransfer
            - (float) $expense
            - (float) $outgoingTransfer;
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => TextNormalizer::singleLine($value),
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value) => TextNormalizer::singleLine($value),
        );
    }
}
