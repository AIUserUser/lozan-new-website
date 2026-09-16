<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * First-party, cookie-based storefront analytics. Stores no IP addresses;
 * visitors are identified only by a random UUID cookie.
 */
class Tracker
{
    public const COOKIE = 'lz_vid';

    public const COOKIE_MINUTES = 60 * 24 * 730;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|java\/|go-http|okhttp|axios|headless|lighthouse|pingdom|uptime|monitor|scanner|semrush|ahrefs/i';

    private const SOURCES = [
        'google' => '/(^|\.)google\./',
        'bing' => '/(^|\.)bing\.com$/',
        'instagram' => '/(^|\.)instagram\.com$/',
        'facebook' => '/(^|\.)(facebook\.com|fb\.com|fb\.me)$/',
        'tiktok' => '/(^|\.)tiktok\.com$/',
        'snapchat' => '/(^|\.)snapchat\.com$/',
        'whatsapp' => '/(^|\.)(whatsapp\.com|wa\.me)$/',
        'x' => '/(^|\.)(t\.co|twitter\.com|x\.com)$/',
    ];

    public function shouldTrack(Request $request): bool
    {
        if ($request->user()?->is_admin) {
            return false;
        }
        if (in_array(strtolower((string) $request->header('Sec-Purpose', $request->header('Purpose', ''))), ['prefetch', 'prerender'], true)) {
            return false;
        }
        $agent = (string) $request->userAgent();

        return $agent !== '' && ! preg_match(self::BOT_PATTERN, $agent);
    }

    /**
     * The visitor id from the cookie, or a new one remembered for this request
     * (the TrackStorefront middleware then sets it as a cookie).
     */
    public function visitorId(Request $request): string
    {
        $id = $request->attributes->get(self::COOKIE) ?? $request->cookie(self::COOKIE);
        if (! is_string($id) || ! Str::isUuid($id)) {
            $id = (string) Str::uuid();
        }
        $request->attributes->set(self::COOKIE, $id);

        return $id;
    }

    public function pageView(Request $request, string $page, ?int $productId = null): void
    {
        $this->record($request, AnalyticsEvent::PAGE_VIEW, [
            'page' => $page,
            'product_id' => $productId,
        ]);
    }

    public function addToCart(Request $request, Product $product, int $quantity, ?array $color, ?string $size): void
    {
        $this->record($request, AnalyticsEvent::ADD_TO_CART, [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'value' => effective_price($product) * $quantity,
            'color' => $this->colorName($color),
            'size' => $size,
        ]);
    }

    public function purchase(Request $request, Order $order): void
    {
        foreach ($order->items as $item) {
            $this->record($request, AnalyticsEvent::PURCHASE, [
                'product_id' => $item->product_id,
                'order_id' => $order->id,
                'quantity' => $item->quantity,
                'value' => (float) $item->unit_price * $item->quantity,
                'color' => $item->color_en ?: $item->color_ar,
                'size' => $item->size,
            ]);
        }
    }

    public static function pageFor(string $path): ?string
    {
        $path = trim($path, '/');
        $path = $path === 'en' ? '' : preg_replace('#^en/#', '', $path);

        return match (true) {
            $path === '' => 'home',
            $path === 'shop' => 'shop',
            str_starts_with($path, 'product/') => 'product',
            $path === 'cart' => 'cart',
            $path === 'checkout' => 'checkout',
            $path === 'order-confirmation' => 'confirmation',
            default => null,
        };
    }

    private function record(Request $request, string $type, array $attributes): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        try {
            [$source, $referrerHost] = $this->source($request);
            AnalyticsEvent::query()->create($attributes + [
                'visitor_id' => $this->visitorId($request),
                'session_hash' => substr(hash('sha256', $request->hasSession() ? $request->session()->getId() : ''), 0, 40),
                'type' => $type,
                'locale' => app()->getLocale() === 'en' ? 'en' : 'ar',
                'device' => $this->device((string) $request->userAgent()),
                'source' => $source,
                'referrer_host' => $referrerHost,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function colorName(?array $color): ?string
    {
        if (! $color) {
            return null;
        }
        $name = trim((string) ($color['en'] ?? '')) ?: trim((string) ($color['ar'] ?? ''));

        return $name !== '' ? Str::limit($name, 80, '') : null;
    }

    private function device(string $agent): string
    {
        if (preg_match('/ipad|tablet|kindle|silk|android(?!.*mobile)/i', $agent)) {
            return 'tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function source(Request $request): array
    {
        $utm = strtolower(trim((string) $request->query('utm_source', '')));
        $host = strtolower((string) parse_url((string) $request->headers->get('referer', ''), PHP_URL_HOST));
        $referrerHost = $host !== '' ? Str::limit($host, 255, '') : null;

        if ($utm !== '') {
            $utm = ['ig' => 'instagram', 'fb' => 'facebook', 'wa' => 'whatsapp', 'twitter' => 'x'][$utm] ?? $utm;

            return [Str::limit($utm, 32, ''), $referrerHost];
        }
        if ($host === '') {
            return ['direct', null];
        }
        $bare = fn (string $h) => preg_replace('/^www\./', '', $h);
        if ($bare($host) === $bare(strtolower($request->getHost()))) {
            return ['internal', $referrerHost];
        }
        foreach (self::SOURCES as $name => $pattern) {
            if (preg_match($pattern, $host)) {
                return [$name, $referrerHost];
            }
        }

        return ['other', $referrerHost];
    }
}
