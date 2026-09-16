<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsReport
{
    public const RANGES = [7, 30, 90];

    public const TIMEZONE = 'Asia/Kuwait';

    /** Kuwait is UTC+3 all year (no DST), so SQL buckets can use a fixed offset. */
    private const UTC_OFFSET_HOURS = 3;

    private CarbonImmutable $start;

    public function __construct(private int $days = 30)
    {
        $this->days = in_array($days, self::RANGES, true) ? $days : 30;
        $this->start = CarbonImmutable::now(self::TIMEZONE)->startOfDay()->subDays($this->days - 1)->utc();
    }

    public function days(): int
    {
        return $this->days;
    }

    public function build(): array
    {
        $totals = $this->totals();

        return [
            'days' => $this->days,
            'hasEvents' => $this->events()->exists(),
            'totals' => $totals,
            'funnel' => $this->funnel($totals),
            'daily' => $this->daily(),
            'hours' => $this->hours(),
            'sources' => $this->breakdown('source', fn (Builder $q) => $q->where('source', '!=', 'internal')),
            'devices' => $this->breakdown('device'),
            'locales' => $this->breakdown('locale'),
            'products' => $this->products(),
            'colors' => $this->choices('color'),
            'sizes' => $this->choices('size'),
            'customers' => $this->customers(),
        ];
    }

    private function events(?string $type = null): Builder
    {
        return AnalyticsEvent::query()
            ->where('created_at', '>=', $this->start)
            ->when($type, fn (Builder $q) => $q->where('type', $type));
    }

    private function orders(): Builder
    {
        return Order::query()->where('created_at', '>=', $this->start);
    }

    private function totals(): array
    {
        $views = $this->events(AnalyticsEvent::PAGE_VIEW);
        $visitors = (clone $views)->distinct()->count('visitor_id');
        // Returning = seen before this period, or came back for more than one visit.
        $returning = AnalyticsEvent::query()
            ->whereIn('visitor_id', (clone $views)->select('visitor_id'))
            ->groupBy('visitor_id')
            ->havingRaw('MIN(created_at) < ? OR COUNT(DISTINCT session_hash) > 1', [$this->start])
            ->pluck('visitor_id')
            ->count();
        $orders = $this->orders()->count();
        $revenue = (float) $this->orders()->sum('subtotal');
        $purchasers = $this->events(AnalyticsEvent::PURCHASE)->distinct()->count('visitor_id');

        return [
            'visitors' => $visitors,
            'newVisitors' => $visitors - $returning,
            'returningVisitors' => $returning,
            'visits' => (clone $views)->distinct()->count('session_hash'),
            'pageViews' => (clone $views)->count(),
            'productViews' => (clone $views)->where('page', 'product')->count(),
            'addToCarts' => $this->events(AnalyticsEvent::ADD_TO_CART)->count(),
            'orders' => $orders,
            'revenue' => $revenue,
            'averageOrder' => $orders > 0 ? $revenue / $orders : 0.0,
            'conversionRate' => $visitors > 0 ? $purchasers / $visitors * 100 : 0.0,
        ];
    }

    private function funnel(array $totals): array
    {
        $distinctVisitors = fn (Builder $q) => $q->distinct()->count('visitor_id');
        $steps = [
            'visited' => $totals['visitors'],
            'viewedProduct' => $distinctVisitors($this->events(AnalyticsEvent::PAGE_VIEW)->where('page', 'product')),
            'addedToBag' => $distinctVisitors($this->events(AnalyticsEvent::ADD_TO_CART)),
            'reachedCheckout' => $distinctVisitors($this->events(AnalyticsEvent::PAGE_VIEW)->where('page', 'checkout')),
            'ordered' => $distinctVisitors($this->events(AnalyticsEvent::PURCHASE)),
        ];
        $top = max(1, $steps['visited']);

        return collect($steps)->map(fn (int $count, string $key) => [
            'key' => $key,
            'count' => $count,
            'percent' => round($count / $top * 100, 1),
        ])->values()->all();
    }

    private function daily(): array
    {
        $day = $this->localBucket('day');
        $rows = $this->events(AnalyticsEvent::PAGE_VIEW)
            ->selectRaw("{$day} as bucket, COUNT(DISTINCT visitor_id) as visitors, SUM(CASE WHEN page = 'product' THEN 1 ELSE 0 END) as product_views")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $series = ['labels' => [], 'visitors' => [], 'productViews' => []];
        $cursor = $this->start->setTimezone(self::TIMEZONE);
        for ($i = 0; $i < $this->days; $i++) {
            $key = $cursor->addDays($i)->format('Y-m-d');
            $series['labels'][] = $key;
            $series['visitors'][] = (int) ($rows[$key]->visitors ?? 0);
            $series['productViews'][] = (int) ($rows[$key]->product_views ?? 0);
        }

        return $series;
    }

    private function hours(): array
    {
        $hour = $this->localBucket('hour');
        $rows = $this->events(AnalyticsEvent::PAGE_VIEW)
            ->selectRaw("{$hour} as bucket, COUNT(*) as views")
            ->groupBy('bucket')
            ->pluck('views', 'bucket');

        return collect(range(0, 23))->map(fn (int $h) => (int) ($rows[$h] ?? $rows[(string) $h] ?? 0))->all();
    }

    private function breakdown(string $column, ?callable $scope = null): array
    {
        $rows = $this->events(AnalyticsEvent::PAGE_VIEW)
            ->when($scope, $scope)
            ->selectRaw("{$column} as label, COUNT(DISTINCT visitor_id) as visitors")
            ->groupBy($column)
            ->orderByDesc('visitors')
            ->get();
        $total = max(1, $rows->sum('visitors'));

        return $rows->map(fn ($r) => [
            'label' => (string) $r->label,
            'count' => (int) $r->visitors,
            'percent' => round($r->visitors / $total * 100, 1),
        ])->all();
    }

    private function products(): array
    {
        $engagement = $this->events()
            ->whereNotNull('product_id')
            ->selectRaw("product_id,
                SUM(CASE WHEN type = 'page_view' THEN 1 ELSE 0 END) as views,
                COUNT(DISTINCT CASE WHEN type = 'page_view' THEN visitor_id END) as viewers,
                SUM(CASE WHEN type = 'add_to_cart' THEN 1 ELSE 0 END) as carts")
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $sales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $this->start)
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as units, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        return Product::query()
            ->with('images')
            ->whereIn('id', $engagement->keys()->merge($sales->keys())->unique())
            ->orWhere('published', true)
            ->get()
            ->map(function (Product $p) use ($engagement, $sales) {
                $e = $engagement[$p->id] ?? null;
                $s = $sales[$p->id] ?? null;
                $viewers = (int) ($e->viewers ?? 0);
                $carts = (int) ($e->carts ?? 0);

                return [
                    'id' => $p->id,
                    'name' => product_display_name($p),
                    'image' => $p->coverUrl(),
                    'editUrl' => route('admin.products.edit', $p),
                    'published' => $p->published,
                    'views' => (int) ($e->views ?? 0),
                    'viewers' => $viewers,
                    'carts' => $carts,
                    'cartRate' => $viewers > 0 ? round(min($carts, $viewers) / $viewers * 100, 1) : null,
                    'units' => (int) ($s->units ?? 0),
                    'revenue' => (float) ($s->revenue ?? 0),
                ];
            })
            ->sortBy([['views', 'desc'], ['units', 'desc'], ['name', 'asc']])
            ->values()
            ->all();
    }

    /** Colors or sizes shoppers picked when adding to the bag, plus what was ordered. */
    private function choices(string $column): array
    {
        $carts = $this->events(AnalyticsEvent::ADD_TO_CART)
            ->whereNotNull($column)
            ->selectRaw("{$column} as label, COUNT(*) as total")
            ->groupBy($column)
            ->pluck('total', 'label');

        $orderExpr = $column === 'color' ? 'COALESCE(order_items.color_en, order_items.color_ar)' : 'order_items.size';
        $ordered = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $this->start)
            ->whereRaw("{$orderExpr} IS NOT NULL")
            ->selectRaw("{$orderExpr} as label, SUM(order_items.quantity) as total")
            ->groupByRaw($orderExpr)
            ->pluck('total', 'label');

        return $carts->keys()->merge($ordered->keys())->unique()
            ->filter(fn ($label) => trim((string) $label) !== '')
            ->map(fn ($label) => [
                'label' => $column === 'size' ? format_eu_size((string) $label) : (string) $label,
                'carts' => (int) ($carts[$label] ?? 0),
                'ordered' => (int) ($ordered[$label] ?? 0),
            ])
            ->sortByDesc(fn ($row) => $row['carts'] + $row['ordered'])
            ->take(8)
            ->values()
            ->all();
    }

    private function customers(): array
    {
        return $this->orders()
            ->selectRaw('phone, MAX(customer_name) as name, COUNT(*) as orders, SUM(subtotal) as total, MAX(created_at) as last_order')
            ->groupBy('phone')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'phone' => $r->phone,
                'orders' => (int) $r->orders,
                'total' => (float) $r->total,
                'lastOrder' => CarbonImmutable::parse($r->last_order, 'UTC')->setTimezone(self::TIMEZONE)->format('Y-m-d'),
            ])
            ->all();
    }

    private function localBucket(string $unit): string
    {
        $offset = self::UTC_OFFSET_HOURS;

        if (DB::connection()->getDriverName() === 'sqlite') {
            return $unit === 'day'
                ? "date(created_at, '+{$offset} hours')"
                : "CAST(strftime('%H', created_at, '+{$offset} hours') AS INTEGER)";
        }

        return $unit === 'day'
            ? "DATE(DATE_ADD(created_at, INTERVAL {$offset} HOUR))"
            : "HOUR(DATE_ADD(created_at, INTERVAL {$offset} HOUR))";
    }
}
