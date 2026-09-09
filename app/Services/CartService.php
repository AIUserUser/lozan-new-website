<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartService
{
    public const KEY = 'lozan_cart';

    public function items(): array
    {
        $items = Session::get(self::KEY, []);

        return is_array($items) ? $items : [];
    }

    public function count(): int
    {
        return array_sum(array_map(fn ($i) => (int) ($i['quantity'] ?? 0), $this->items()));
    }

    public function subtotal(): float
    {
        $sum = 0;
        foreach ($this->items() as $item) {
            $sum += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0));
        }

        return round($sum, 3);
    }

    public function add(Product $product, int $qty, ?array $color, ?string $size, bool $backorder = false): void
    {
        $qty = max(1, min(99, $qty));
        $lineId = $this->lineId($product->id, $color, $size, $backorder);
        $items = $this->items();
        $found = false;
        foreach ($items as &$item) {
            if (($item['line_id'] ?? '') === $lineId) {
                $item['quantity'] = min(99, ((int) $item['quantity']) + $qty);
                $found = true;
                break;
            }
        }
        unset($item);
        if (! $found) {
            $items[] = [
                'line_id' => $lineId,
                'product_id' => $product->id,
                'name_ar' => $product->name,
                'name_en' => $product->name_en,
                'color' => $color,
                'size' => $size,
                'price' => effective_price($product),
                'original_price' => is_on_sale($product) ? (float) $product->price : null,
                'image_url' => $product->coverUrl(),
                'quantity' => $qty,
                'backorder' => $backorder,
            ];
        }
        Session::put(self::KEY, array_values($items));
    }

    public function updateQty(string $lineId, int $qty): void
    {
        $qty = max(1, min(99, $qty));
        $items = $this->items();
        foreach ($items as &$item) {
            if (($item['line_id'] ?? '') === $lineId) {
                $item['quantity'] = $qty;
            }
        }
        unset($item);
        Session::put(self::KEY, array_values($items));
    }

    public function remove(string $lineId): void
    {
        $items = array_values(array_filter(
            $this->items(),
            fn ($item) => ($item['line_id'] ?? '') !== $lineId
        ));
        Session::put(self::KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    public function lineId(int|string $productId, ?array $color, ?string $size, bool $backorder): string
    {
        $base = (string) $productId;
        if ($color && (trim((string) ($color['ar'] ?? '')) || trim((string) ($color['en'] ?? '')) || trim((string) ($color['hex'] ?? '')))) {
            $base .= '::c:'.trim((string) ($color['ar'] ?? '')).'|'.trim((string) ($color['en'] ?? '')).'|'.trim((string) ($color['hex'] ?? ''));
        }
        if ($size) {
            $base .= '::s:'.trim($size);
        }

        return $backorder ? $base.'::backorder' : $base;
    }
}
