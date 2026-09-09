<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\TelegramSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportSupabaseCommand extends Command
{
    protected $signature = 'lozan:import-supabase';

    protected $description = 'Import products, images, orders, and telegram subscribers from Supabase';

    public function handle(): int
    {
        $url = rtrim((string) config('lozan.supabase_url'), '/');
        $key = (string) config('lozan.supabase_anon_key');
        if ($url === '' || $key === '') {
            $this->error('Set SUPABASE_URL and SUPABASE_ANON_KEY in .env');

            return self::FAILURE;
        }

        $this->importProducts($url, $key);
        $this->importOrders($url, $key);
        $this->importTelegram($url, $key);
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function rows(string $url, string $key, string $table): array
    {
        $res = Http::withHeaders([
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
        ])->get($url.'/rest/v1/'.$table, ['select' => '*']);
        if (! $res->successful()) {
            $this->warn($table.' fetch failed: '.$res->status().' '.$res->body());

            return [];
        }

        return $res->json() ?: [];
    }

    private function importProducts(string $url, string $key): void
    {
        $rows = $this->rows($url, $key, 'products');
        $this->info('Products: '.count($rows));
        foreach ($rows as $row) {
            $legacy = $row['id'] ?? null;
            $product = $legacy ? Product::query()->firstOrNew(['legacy_id' => $legacy]) : new Product;
            $name = (string) ($row['name'] ?? 'product');
            $nameEn = (string) ($row['name_en'] ?? '');
            $product->fill([
                'legacy_id' => $legacy,
                'name' => $name,
                'name_en' => $nameEn,
                'description' => $row['description'] ?? '',
                'price' => $row['price'] ?? 0,
                'offer_price' => $row['offer_price'] ?? null,
                'category' => $row['category'] ?? 'gown',
                'published' => (bool) ($row['published'] ?? true),
                'stock_status' => $row['stock_status'] ?? 'in_stock',
                'cover_index' => (int) ($row['cover_index'] ?? 0),
                'slug' => $product->slug ?: Product::uniqueSlug($nameEn, $name, $product->id),
            ]);
            if ($row['created_at'] ?? null) {
                $product->created_at = $row['created_at'];
            }
            $product->save();
            if (! $product->slug) {
                $product->slug = Product::uniqueSlug($nameEn, $name, $product->id);
                $product->save();
            }

            $product->colors()->delete();
            $product->sizes()->delete();
            $colors = $row['available_colors'] ?? [];
            if (is_string($colors)) {
                $colors = json_decode($colors, true) ?: [];
            }
            foreach ($colors as $i => $c) {
                if (is_string($c)) {
                    $c = ['ar' => $c, 'en' => '', 'hex' => ''];
                }
                ProductColor::query()->create([
                    'product_id' => $product->id,
                    'name_ar' => $c['ar'] ?? $c['ar_name'] ?? '',
                    'name_en' => $c['en'] ?? $c['en_name'] ?? '',
                    'hex' => $c['hex'] ?? '',
                    'sort_order' => $i,
                ]);
            }
            $sizes = $row['available_sizes'] ?? [];
            if (is_string($sizes)) {
                $sizes = json_decode($sizes, true) ?: [];
            }
            foreach ($sizes as $i => $s) {
                $s = trim(preg_replace('/^eu\s*/i', '', (string) $s) ?? (string) $s);
                if ($s === '') {
                    continue;
                }
                ProductSize::query()->create([
                    'product_id' => $product->id,
                    'size' => $s,
                    'sort_order' => $i,
                ]);
            }

            $urls = $row['image_urls'] ?? [];
            if (is_string($urls)) {
                $urls = json_decode($urls, true) ?: [];
            }
            if (! $urls && ! empty($row['image_url'])) {
                $urls = [$row['image_url']];
            }
            if ($product->images()->exists()) {
                continue;
            }
            foreach ($urls as $i => $imageUrl) {
                $path = $this->downloadImage((string) $imageUrl, $product->id, $i);
                if (! $path) {
                    continue;
                }
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'sort_order' => $i,
                ]);
            }
        }
    }

    private function downloadImage(string $url, int $productId, int $index): ?string
    {
        if ($url === '') {
            return null;
        }
        try {
            $res = Http::timeout(30)->get($url);
            if (! $res->successful()) {
                $this->warn('Image failed: '.$url);

                return $url;
            }
            $ext = 'jpg';
            $ct = $res->header('Content-Type');
            if (str_contains((string) $ct, 'png')) {
                $ext = 'png';
            } elseif (str_contains((string) $ct, 'webp')) {
                $ext = 'webp';
            }
            $path = 'products/'.$productId.'/'.Str::padLeft((string) $index, 2, '0').'.'.$ext;
            Storage::disk('public')->put($path, $res->body());

            return $path;
        } catch (\Throwable $e) {
            $this->warn($e->getMessage());

            return $url;
        }
    }

    private function importOrders(string $url, string $key): void
    {
        $rows = $this->rows($url, $key, 'orders');
        $this->info('Orders: '.count($rows));
        foreach ($rows as $row) {
            $legacy = $row['id'] ?? null;
            $order = $legacy ? Order::query()->firstOrNew(['legacy_id' => $legacy]) : new Order;
            $order->fill([
                'legacy_id' => $legacy,
                'customer_name' => $row['customer_name'] ?? '',
                'phone' => $row['phone'] ?? '',
                'address' => $row['address'] ?? '',
                'wants_delivery' => (bool) ($row['wants_delivery'] ?? false),
                'subtotal' => $row['subtotal'] ?? 0,
                'status' => $row['status'] ?? 'pending',
            ]);
            $order->save();
            if ($order->items()->exists()) {
                continue;
            }
            $items = $row['items'] ?? [];
            if (is_string($items)) {
                $items = json_decode($items, true) ?: [];
            }
            foreach ($items as $it) {
                $color = $it['color'] ?? null;
                $colorAr = is_array($color) ? ($color['ar'] ?? '') : (string) ($it['colorAr'] ?? $color ?? '');
                $colorEn = is_array($color) ? ($color['en'] ?? '') : (string) ($it['colorEn'] ?? '');
                $colorHex = is_array($color) ? ($color['hex'] ?? '') : '';
                $product = null;
                if (! empty($it['productId'])) {
                    $product = Product::query()->where('legacy_id', $it['productId'])->first();
                }
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'name_ar' => $it['nameAr'] ?? $it['name'] ?? '',
                    'name_en' => $it['nameEn'] ?? '',
                    'color_ar' => $colorAr,
                    'color_en' => $colorEn,
                    'color_hex' => $colorHex,
                    'size' => $it['size'] ?? null,
                    'quantity' => $it['quantity'] ?? $it['qty'] ?? 1,
                    'unit_price' => $it['price'] ?? 0,
                    'backorder' => (bool) ($it['backorder'] ?? false),
                    'image_path' => $it['imageUrl'] ?? null,
                ]);
            }
        }
    }

    private function importTelegram(string $url, string $key): void
    {
        $rows = $this->rows($url, $key, 'telegram_subscribers');
        $this->info('Telegram: '.count($rows));
        foreach ($rows as $row) {
            if (empty($row['chat_id'])) {
                continue;
            }
            TelegramSubscriber::query()->updateOrCreate(
                ['chat_id' => $row['chat_id']],
                [
                    'username' => $row['username'] ?? null,
                    'first_name' => $row['first_name'] ?? null,
                    'last_name' => $row['last_name'] ?? null,
                    'active' => (bool) ($row['active'] ?? true),
                ]
            );
        }
    }
}
