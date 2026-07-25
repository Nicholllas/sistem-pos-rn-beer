<?php

namespace App\Models;

use App\Support\TextNormalizer;
use Database\Factories\FinanceCategoryFactory;
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
    'is_system',
    'is_active',
    'created_by',
    'updated_by',
])]
class FinanceCategory extends Model
{
    /** @use HasFactory<FinanceCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            FinanceTransaction::class,
            'category_id'
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
