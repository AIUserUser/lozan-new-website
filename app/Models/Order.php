<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'wants_delivery' => 'boolean',
            'subtotal' => 'decimal:3',
        ];
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
