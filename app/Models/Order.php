<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'legacy_id',
    'customer_name',
    'phone',
    'address',
    'wants_delivery',
    'subtotal',
    'status',
    'discarded_at',
])]
class Order extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'contacted', 'done'];

    protected function casts(): array
    {
        return [
            'wants_delivery' => 'boolean',
            'subtotal' => 'decimal:3',
            'discarded_at' => 'datetime',
        ];
    }

    /** Orders that count toward reports (not discarded). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('discarded_at');
    }

    public function scopeDiscarded(Builder $query): Builder
    {
        return $query->whereNotNull('discarded_at');
    }

    public function isDiscarded(): bool
    {
        return $this->discarded_at !== null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shortRef(): string
    {
        return substr((string) ($this->legacy_id ?: $this->id), 0, 8);
    }
}
