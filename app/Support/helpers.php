<?php

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Support\Arr;

if (! function_exists('lozan_t')) {
    function lozan_t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $locale = $locale === 'en' ? 'en' : 'ar';
        static $cache = [];
        if (! isset($cache[$locale])) {
            $path = lang_path($locale.'.json');
            $cache[$locale] = is_file($path)
                ? json_decode((string) file_get_contents($path), true)
                : [];
        }
        $value = data_get($cache[$locale], $key, $key);
        if (! is_string($value)) {
            $value = is_scalar($value) ? (string) $value : $key;
        }
        foreach ($replace as $search => $replacement) {
            $value = str_replace('{'.$search.'}', (string) $replacement, $value);
        }

        return $value;
    }
}

if (! function_exists('locale_url')) {
    function locale_url(string $path = '', ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $path = ltrim($path, '/');
        if ($locale === 'en') {
            return url($path === '' ? 'en' : 'en/'.$path);
        }

        return url($path === '' ? '/' : '/'.$path);
    }
}

if (! function_exists('switch_locale_url')) {
    function switch_locale_url(string $targetLocale): string
    {
        $request = request();
        $path = $request?->path() ?? '';
        if (str_starts_with($path, 'en/') || $path === 'en') {
            $path = $path === 'en' ? '' : substr($path, 3);
        }
        if (str_starts_with($path, 'admin') || $path === 'telegram/webhook') {
            return locale_url($path, $targetLocale);
        }

        return locale_url($path, $targetLocale);
    }
}

if (! function_exists('product_url')) {
    function product_url(Product $product, ?string $locale = null): string
    {
        return locale_url('product/'.$product->slug, $locale);
    }
}

if (! function_exists('format_kwd')) {
    function format_kwd(mixed $value, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }
        $formatted = number_format((float) $value, 3, '.', $locale === 'ar' ? ',' : ',');
        if ($locale === 'ar') {
            $formatted = number_format((float) $value, 3, '.', '٬');
        }

        return $formatted.' '.lozan_t('currency.symbol', [], $locale);
    }
}

if (! function_exists('product_display_name')) {
    function product_display_name(Product $product, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        if ($locale === 'en' && $product->name_en) {
            return $product->name_en;
        }

        return (string) $product->name;
    }
}

if (! function_exists('color_key')) {
    function color_key(?array $color): string
    {
        if (! $color) {
            return '';
        }

        return implode('|', [
            (string) ($color['ar'] ?? ''),
            (string) ($color['en'] ?? ''),
            (string) ($color['hex'] ?? ''),
        ]);
    }
}

if (! function_exists('color_label')) {
    function color_label(?array $color, ?string $locale = null): string
    {
        if (! $color) {
            return '';
        }
        $locale = $locale ?: app()->getLocale();
        if ($locale === 'en' && ! empty($color['en'])) {
            return (string) $color['en'];
        }

        return (string) ($color['ar'] ?? $color['en'] ?? '');
    }
}

if (! function_exists('format_eu_size')) {
    function format_eu_size(?string $size): string
    {
        $s = trim((string) $size);
        $s = preg_replace('/^eu\s*/i', '', $s) ?? $s;
        $s = trim($s);

        return $s === '' ? '' : 'EU '.$s;
    }
}

if (! function_exists('cart_count')) {
    function cart_count(): int
    {
        return app(CartService::class)->count();
    }
}

if (! function_exists('is_on_sale')) {
    function is_on_sale(Product $product): bool
    {
        if ($product->offer_price === null) {
            return false;
        }
        $off = (float) $product->offer_price;
        $reg = (float) $product->price;

        return $off >= 0 && $reg > 0 && $off < $reg;
    }
}

if (! function_exists('effective_price')) {
    function effective_price(Product $product): float
    {
        return is_on_sale($product) ? (float) $product->offer_price : (float) $product->price;
    }
}

if (! function_exists('discount_percent')) {
    function discount_percent(Product $product): int
    {
        if (! is_on_sale($product)) {
            return 0;
        }
        $reg = (float) $product->price;
        $off = (float) $product->offer_price;
        if ($reg <= 0) {
            return 0;
        }

        return max(0, min(99, (int) floor((($reg - $off) / $reg) * 100)));
    }
}

if (! function_exists('gcc_countries')) {
    function gcc_countries(): array
    {
        return [
            ['code' => 'KW', 'dial' => '965', 'flag' => '🇰🇼', 'length' => 8, 'ar' => 'الكويت', 'en' => 'Kuwait'],
            ['code' => 'SA', 'dial' => '966', 'flag' => '🇸🇦', 'length' => 9, 'ar' => 'السعودية', 'en' => 'Saudi Arabia'],
            ['code' => 'AE', 'dial' => '971', 'flag' => '🇦🇪', 'length' => 9, 'ar' => 'الإمارات', 'en' => 'UAE'],
            ['code' => 'QA', 'dial' => '974', 'flag' => '🇶🇦', 'length' => 8, 'ar' => 'قطر', 'en' => 'Qatar'],
            ['code' => 'BH', 'dial' => '973', 'flag' => '🇧🇭', 'length' => 8, 'ar' => 'البحرين', 'en' => 'Bahrain'],
            ['code' => 'OM', 'dial' => '968', 'flag' => '🇴🇲', 'length' => 8, 'ar' => 'عُمان', 'en' => 'Oman'],
        ];
    }
}

if (! function_exists('gcc_country')) {
    function gcc_country(?string $code): array
    {
        $code = strtoupper((string) $code);

        return Arr::first(gcc_countries(), fn ($c) => $c['code'] === $code) ?: gcc_countries()[0];
    }
}

if (! function_exists('local_phone_digits')) {
    function local_phone_digits(string $raw, array $country): string
    {
        $d = preg_replace('/\D/', '', $raw) ?? '';
        if ($d === '') {
            return '';
        }
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }
        if (str_starts_with($d, $country['dial'])) {
            $d = substr($d, strlen($country['dial']));
        }
        if (str_starts_with($d, '0')) {
            $d = substr($d, 1);
        }

        return $d;
    }
}

if (! function_exists('format_e164')) {
    function format_e164(string $raw, array $country): string
    {
        $local = local_phone_digits($raw, $country);

        return $local === '' ? '' : '+'.$country['dial'].$local;
    }
}

if (! function_exists('color_presets')) {
    function color_presets(): array
    {
        return [
            ['ar' => 'أسود', 'en' => 'Black', 'hex' => '#1a1a1a'],
            ['ar' => 'أبيض', 'en' => 'White', 'hex' => '#f7f5f0'],
            ['ar' => 'بيج', 'en' => 'Beige', 'hex' => '#d8c7a7'],
            ['ar' => 'بني', 'en' => 'Brown', 'hex' => '#6b4a2b'],
            ['ar' => 'كريمي', 'en' => 'Cream', 'hex' => '#f3e9d2'],
            ['ar' => 'ذهبي', 'en' => 'Gold', 'hex' => '#c9a24a'],
            ['ar' => 'فضي', 'en' => 'Silver', 'hex' => '#c0c0c0'],
            ['ar' => 'كحلي', 'en' => 'Navy', 'hex' => '#1d2a4d'],
            ['ar' => 'أزرق', 'en' => 'Blue', 'hex' => '#3a6ea5'],
            ['ar' => 'أزرق فاتح', 'en' => 'Light blue', 'hex' => '#a9c8e8'],
            ['ar' => 'أزرق سماوي', 'en' => 'Sky blue', 'hex' => '#7cc1e3'],
            ['ar' => 'تركوازي', 'en' => 'Turquoise', 'hex' => '#2ea7a0'],
            ['ar' => 'أخضر', 'en' => 'Green', 'hex' => '#4a7c45'],
            ['ar' => 'أخضر زيتي', 'en' => 'Olive', 'hex' => '#6b6a3a'],
            ['ar' => 'أخضر فاتح', 'en' => 'Mint', 'hex' => '#a8d5b3'],
            ['ar' => 'أصفر', 'en' => 'Yellow', 'hex' => '#e8c547'],
            ['ar' => 'أصفر فاتح', 'en' => 'Light yellow', 'hex' => '#f5e8a3'],
            ['ar' => 'برتقالي', 'en' => 'Orange', 'hex' => '#d97a3c'],
            ['ar' => 'مرجاني', 'en' => 'Coral', 'hex' => '#e8736b'],
            ['ar' => 'أحمر', 'en' => 'Red', 'hex' => '#b8323a'],
            ['ar' => 'عنابي', 'en' => 'Burgundy', 'hex' => '#6e1e2c'],
            ['ar' => 'وردي', 'en' => 'Pink', 'hex' => '#e8a3b8'],
            ['ar' => 'وردي فاتح', 'en' => 'Blush', 'hex' => '#f3d3d8'],
            ['ar' => 'فوشيا', 'en' => 'Fuchsia', 'hex' => '#c44a8a'],
            ['ar' => 'بنفسجي', 'en' => 'Purple', 'hex' => '#7a4a8c'],
            ['ar' => 'موف', 'en' => 'Lavender', 'hex' => '#b8a3d3'],
            ['ar' => 'رمادي', 'en' => 'Grey', 'hex' => '#8a8a8a'],
            ['ar' => 'رمادي فاتح', 'en' => 'Light grey', 'hex' => '#c8c8c8'],
        ];
    }
}

if (! function_exists('eu_size_presets')) {
    function eu_size_presets(): array
    {
        return [32, 34, 36, 38, 40, 42, 44, 46, 48];
    }
}

if (! function_exists('lozan_location')) {
    function lozan_location(): array
    {
        return [
            'countryCode' => 'KW',
            'regionCode' => 'KW-FA',
            'placename' => 'Farwaniya, Kuwait',
            'latitude' => 29.2775,
            'longitude' => 47.9583,
            'currency' => 'KWD',
        ];
    }
}
