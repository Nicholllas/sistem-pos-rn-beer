<?php

namespace App\Models;

use App\Support\TextNormalizer;
use Database\Factories\FinanceAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    'sales_payment_method',
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

    public function scopeWithCurrentBalance(Builder $query): Builder
    {
        return $query
            ->withSum([
                'transactions as posted_income' => fn (Builder $q) => $q
                    ->where('status', 'posted')
                    ->where('type', 'income'),
            ], 'amount')
            ->withSum([
                'transactions as posted_expense' => fn (Builder $q) => $q
                    ->where('status', 'posted')
                    ->where('type', 'expense'),
            ], 'amount')
            ->withSum([
                'transactions as outgoing_transfer' => fn (Builder $q) => $q
                    ->where('status', 'posted')
                    ->where('type', 'transfer'),
            ], 'amount')
            ->withSum([
                'incomingTransfers as incoming_transfer' => fn (Builder $q) => $q
                    ->where('status', 'posted')
                    ->where('type', 'transfer'),
            ], 'amount');
    }

    public function getCurrentBalanceAttribute(): float
    {
        if (
            array_key_exists('posted_income', $this->attributes)
            || array_key_exists('posted_expense', $this->attributes)
            || array_key_exists('incoming_transfer', $this->attributes)
            || array_key_exists('outgoing_transfer', $this->attributes)
        ) {
            return (float) $this->opening_balance
                + (float) ($this->posted_income ?? 0)
                + (float) ($this->incoming_transfer ?? 0)
                - (float) ($this->posted_expense ?? 0)
                - (float) ($this->outgoing_transfer ?? 0);
        }

        $transactionSums = $this->transactions()
            ->where('status', 'posted')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense,
                COALESCE(SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END), 0) as outgoing_transfer
            ")
            ->first();

        $incomingTransfer = (float) $this->incomingTransfers()
            ->where('status', 'posted')
            ->where('type', 'transfer')
            ->sum('amount');

        return (float) $this->opening_balance
            + (float) ($transactionSums->income ?? 0)
            + $incomingTransfer
            - (float) ($transactionSums->expense ?? 0)
            - (float) ($transactionSums->outgoing_transfer ?? 0);
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
