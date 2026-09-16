<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics\AnalyticsReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::query()->create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass', 'is_admin' => true]);
    }

    private function order(array $attributes = []): Order
    {
        return Order::query()->create($attributes + [
            'customer_name' => 'Sara',
            'phone' => '+96551234567',
            'subtotal' => 49.5,
            'status' => 'pending',
        ]);
    }

    public function test_discard_and_restore_order(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)->from('/admin/orders')
            ->patch(route('admin.orders.discard', $order))
            ->assertRedirect('/admin/orders');
        $this->assertNotNull($order->fresh()->discarded_at);
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($this->admin)->patch(route('admin.orders.restore', $order));
        $this->assertNull($order->fresh()->discarded_at);
    }

    public function test_guests_cannot_discard_orders(): void
    {
        $order = $this->order();

        $this->patch(route('admin.orders.discard', $order))->assertRedirect(route('admin.login'));
        $this->assertNull($order->fresh()->discarded_at);
    }

    public function test_orders_page_filters_by_status_and_search(): void
    {
        $this->order(['customer_name' => 'Pending Paula']);
        $this->order(['customer_name' => 'Done Dana', 'status' => 'done', 'phone' => '+96599887766']);
        $this->order(['customer_name' => 'Discarded Dina', 'discarded_at' => now()]);

        $this->actingAs($this->admin)->get('/admin/orders')
            ->assertOk()->assertSee('Pending Paula')->assertSee('Done Dana')->assertDontSee('Discarded Dina');

        $this->actingAs($this->admin)->get('/admin/orders?status=done')
            ->assertOk()->assertSee('Done Dana')->assertDontSee('Pending Paula');

        $this->actingAs($this->admin)->get('/admin/orders?status=discarded')
            ->assertOk()->assertSee('Discarded Dina')->assertDontSee('Pending Paula');

        $this->actingAs($this->admin)->get('/admin/orders?q=99887766')
            ->assertOk()->assertSee('Done Dana')->assertDontSee('Pending Paula');

        $this->actingAs($this->admin)->get('/admin/orders?q=paula')
            ->assertOk()->assertSee('Pending Paula')->assertDontSee('Done Dana');
    }

    public function test_discarded_orders_are_excluded_from_analytics(): void
    {
        $product = Product::query()->create(['slug' => 'dress', 'name' => 'فستان', 'price' => 50, 'category' => 'gown', 'published' => true, 'stock_status' => 'in_stock']);
        $kept = $this->order(['subtotal' => 50]);
        $discarded = $this->order(['subtotal' => 100, 'phone' => '+96511111111', 'discarded_at' => now()]);
        foreach ([$kept, $discarded] as $i => $order) {
            $order->items()->create(['product_id' => $product->id, 'name_ar' => 'فستان', 'quantity' => $i + 1, 'unit_price' => 50, 'size' => '38']);
            AnalyticsEvent::query()->create([
                'visitor_id' => sprintf('%08d-1111-4111-8111-111111111111', $i),
                'session_hash' => 's'.$i,
                'type' => AnalyticsEvent::PURCHASE,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'quantity' => $i + 1,
                'locale' => 'ar',
                'device' => 'mobile',
                'source' => 'direct',
            ]);
        }

        $report = (new AnalyticsReport(30))->build();

        $this->assertSame(1, $report['totals']['orders']);
        $this->assertSame(50.0, $report['totals']['revenue']);
        $this->assertSame(1, $report['products'][0]['units']);
        $this->assertSame(1, $report['funnel'][4]['count']);
        $this->assertSame(1, $report['sizes'][0]['ordered']);
        $this->assertSame(['+96551234567'], array_column($report['customers'], 'phone'));
    }
}
