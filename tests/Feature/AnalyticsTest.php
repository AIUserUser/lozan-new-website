<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics\AnalyticsReport;
use App\Services\Analytics\Tracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    private function product(array $attributes = []): Product
    {
        $product = Product::query()->create($attributes + [
            'slug' => 'royal-dress',
            'name' => 'فستان ملكي',
            'name_en' => 'Royal Dress',
            'price' => 49.5,
            'category' => 'gown',
            'published' => true,
            'stock_status' => 'in_stock',
        ]);
        $product->sizes()->create(['size' => '38', 'sort_order' => 0]);
        $product->colors()->create(['name_ar' => 'أسود', 'name_en' => 'Black', 'hex' => '#000000', 'sort_order' => 0]);

        return $product;
    }

    public function test_product_page_view_is_recorded_with_visitor_cookie(): void
    {
        $product = $this->product();

        $response = $this->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => 'https://l.instagram.com/'])
            ->get('/en/product/royal-dress');

        $response->assertOk()->assertCookie(Tracker::COOKIE);
        $event = AnalyticsEvent::query()->sole();
        $this->assertSame(AnalyticsEvent::PAGE_VIEW, $event->type);
        $this->assertSame('product', $event->page);
        $this->assertSame($product->id, $event->product_id);
        $this->assertSame('en', $event->locale);
        $this->assertSame('mobile', $event->device);
        $this->assertSame('instagram', $event->source);
    }

    public function test_returning_visitor_keeps_the_same_id_and_internal_referrers_are_marked(): void
    {
        $this->product();
        $visitorId = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';

        $this->withHeaders(['User-Agent' => self::BROWSER, 'Referer' => url('/shop')])
            ->withCookie(Tracker::COOKIE, $visitorId)
            ->get('/product/royal-dress')
            ->assertOk();

        $event = AnalyticsEvent::query()->sole();
        $this->assertSame($visitorId, $event->visitor_id);
        $this->assertSame('internal', $event->source);
    }

    public function test_bots_and_admins_are_not_tracked(): void
    {
        $this->product();

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->get('/shop')->assertOk();

        $admin = User::query()->create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass', 'is_admin' => true]);
        $this->actingAs($admin)->withHeaders(['User-Agent' => self::BROWSER])->get('/shop')->assertOk();

        $this->assertSame(0, AnalyticsEvent::query()->count());
    }

    public function test_add_to_cart_and_purchase_are_recorded(): void
    {
        $product = $this->product();
        $this->withHeaders(['User-Agent' => self::BROWSER]);

        $added = $this->post('/product/royal-dress/cart', ['qty' => 2, 'size' => '38', 'color' => 'أسود|Black|#000000'])->assertRedirect();
        $this->withCookie(Tracker::COOKIE, $added->getCookie(Tracker::COOKIE)->getValue());
        $this->post('/checkout', [
            'name' => 'Sara',
            'country' => 'KW',
            'phone' => '51234567',
            'phone_confirm' => '51234567',
        ])->assertRedirect();

        $cart = AnalyticsEvent::query()->where('type', AnalyticsEvent::ADD_TO_CART)->sole();
        $this->assertSame($product->id, $cart->product_id);
        $this->assertSame(2, $cart->quantity);
        $this->assertSame('Black', $cart->color);
        $this->assertSame('38', $cart->size);

        $purchase = AnalyticsEvent::query()->where('type', AnalyticsEvent::PURCHASE)->sole();
        $this->assertSame($cart->visitor_id, $purchase->visitor_id);
        $this->assertSame('99.000', $purchase->value);
    }

    public function test_report_aggregates_engagement(): void
    {
        $product = $this->product();
        $event = fn (string $visitor, string $type, array $extra = []) => AnalyticsEvent::query()->create($extra + [
            'visitor_id' => $visitor,
            'session_hash' => sha1($visitor),
            'type' => $type,
            'locale' => 'ar',
            'device' => 'mobile',
            'source' => 'direct',
            'created_at' => now(),
        ]);
        $a = '11111111-1111-4111-8111-111111111111';
        $b = '22222222-2222-4222-8222-222222222222';
        $event($a, AnalyticsEvent::PAGE_VIEW, ['page' => 'home']);
        $event($a, AnalyticsEvent::PAGE_VIEW, ['page' => 'product', 'product_id' => $product->id]);
        $event($a, AnalyticsEvent::ADD_TO_CART, ['product_id' => $product->id, 'size' => '38', 'quantity' => 1]);
        $event($b, AnalyticsEvent::PAGE_VIEW, ['page' => 'product', 'product_id' => $product->id]);
        $event($b, AnalyticsEvent::PAGE_VIEW, ['page' => 'product', 'product_id' => $product->id]);
        $event($b, AnalyticsEvent::PAGE_VIEW, ['page' => 'home', 'created_at' => now()->subDays(200)]);

        $report = (new AnalyticsReport(30))->build();

        $this->assertSame(2, $report['totals']['visitors']);
        $this->assertSame(1, $report['totals']['returningVisitors']);
        $this->assertSame(3, $report['totals']['productViews']);
        $this->assertSame([2, 2, 1, 0, 0], array_column($report['funnel'], 'count'));
        $this->assertSame(3, $report['products'][0]['views']);
        $this->assertSame(2, $report['products'][0]['viewers']);
        $this->assertSame(50.0, $report['products'][0]['cartRate']);
        $this->assertSame('EU 38', $report['sizes'][0]['label']);
        $this->assertSame(2, array_sum($report['daily']['visitors']));
    }

    public function test_dashboard_requires_admin_and_renders(): void
    {
        $this->get('/admin/analytics')->assertRedirect(route('admin.login'));

        $this->product();
        $admin = User::query()->create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass', 'is_admin' => true]);

        foreach (['7', '30', '90', 'bogus'] as $range) {
            $this->actingAs($admin)->get('/admin/analytics?range='.$range)
                ->assertOk()
                ->assertSee('chart-trend', false)
                ->assertSee('فستان ملكي');
        }
    }
}
