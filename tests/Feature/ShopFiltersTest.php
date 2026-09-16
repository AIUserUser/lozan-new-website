<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, string $category, array $colors, array $sizes): Product
    {
        $product = Product::query()->create([
            'slug' => $slug, 'name' => $slug, 'name_en' => ucfirst($slug), 'price' => 40,
            'category' => $category, 'published' => true, 'stock_status' => 'in_stock',
        ]);
        foreach ($colors as $i => [$ar, $en, $hex]) {
            $product->colors()->create(['name_ar' => $ar, 'name_en' => $en, 'hex' => $hex, 'sort_order' => $i]);
        }
        foreach ($sizes as $i => $size) {
            $product->sizes()->create(['size' => $size, 'sort_order' => $i]);
        }

        return $product;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Same color with different hex codes must group into one choice.
        $this->product('alpha', 'gown', [['عنابي', 'Burgundy', '#6e1423'], ['كحلي', 'Navy', '#1d2a4d']], ['38', '40']);
        $this->product('beta', 'gown', [['عنابي', 'Burgundy', '#800020']], ['40']);
        $this->product('gamma', 'party', [['أسود', 'Black', '#111111']], ['36']);
    }

    public function test_facets_group_colors_by_name_and_count_against_other_filters(): void
    {
        $catalog = app(CatalogService::class);
        $products = $catalog->published();

        $all = $catalog->facets($products, 'all', null, null);
        $this->assertSame(['burgundy' => 2, 'black' => 1, 'navy' => 1], collect($all['colors'])->pluck('count', 'key')->all());
        $this->assertSame(['36' => 1, '38' => 1, '40' => 2], collect($all['sizes'])->pluck('count', 'value')->all());

        $gownsInBurgundy = $catalog->facets($products, 'gown', 'burgundy', 'EU 38');
        $this->assertSame(['burgundy' => 1, 'navy' => 1], collect($gownsInBurgundy['colors'])->pluck('count', 'key')->all());
        $this->assertSame(['38' => 1, '40' => 2], collect($gownsInBurgundy['sizes'])->pluck('count', 'value')->all());

        $this->assertSame(['alpha', 'beta'], $catalog->filter($products, 'all', 'burgundy', null)->pluck('slug')->sort()->values()->all());
    }

    public function test_shop_page_renders_filters_with_state_and_clear_links(): void
    {
        $response = $this->get('/en/shop?category=gown&color=burgundy&size=40')->assertOk();

        $response->assertSee('class="sf-seg"', false)
            ->assertSee('2 pieces')
            ->assertSee('href="'.url('/en/shop').'?category=gown&amp;size=40" rel="nofollow" aria-label="Remove filter: Burgundy"', false)
            ->assertSee('href="'.url('/en/shop').'?category=gown" rel="nofollow">Clear filter', false)
            ->assertSee('Alpha')->assertSee('Beta')->assertDontSee('Gamma');

        $this->get('/en/shop?category=party')->assertOk()->assertSee('1 piece')->assertSee('Gamma')->assertDontSee('Alpha');
        $this->get('/')->assertOk()->assertSee('data-shop-filters', false);
    }
}
