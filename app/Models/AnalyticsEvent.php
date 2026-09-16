<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'visitor_id',
    'session_hash',
    'type',
    'page',
    'product_id',
    'order_id',
    'quantity',
    'value',
    'color',
    'size',
    'locale',
    'device',
    'source',
    'referrer_host',
    'created_at',
])]
class AnalyticsEvent extends Model
{
    public const UPDATED_AT = null;

    public const PAGE_VIEW = 'page_view';

    public const ADD_TO_CART = 'add_to_cart';

    public const PURCHASE = 'purchase';

    protected function casts(): array
    {
        return [
            'value' => 'decimal:3',
            'quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
