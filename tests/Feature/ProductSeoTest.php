<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductSeoTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        return Product::query()->create($attributes + [
            'slug' => 'royal-dress',
            'name' => 'فستان ملكي',
            'name_en' => 'Royal Dress',
            'description' => 'وصف عربي للفستان',
            'price' => 49.5,
            'category' => 'gown',
            'published' => true,
            'stock_status' => 'in_stock',
        ]);
    }

    private function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass', 'is_admin' => true]);
    }

    private function jsonLd(TestResponse $response): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $m);

        return collect(json_decode($m[1] ?? '{}', true)['@graph'] ?? [])->keyBy('@type')->all();
    }

    private function formData(array $overrides = []): array
    {
        Storage::fake('public');

        return $overrides + [
            'images' => [UploadedFile::fake()->image('dress.jpg', 600, 800)],
            'name' => 'فستان ملكي',
            'name_en' => 'Royal Dress',
            'price' => '49.5',
            'category' => 'gown',
            'published' => '1',
            'stock_status' => 'in_stock',
        ];
    }

    public function test_custom_seo_title_and_description_are_used_per_locale(): void
    {
        $this->product([
            'description_en' => 'English description of the dress',
            'seo_title_ar' => 'عنوان مخصص',
            'seo_title_en' => 'Custom English Title',
            'seo_description_en' => 'Custom English meta description.',
        ]);

        $this->get('/product/royal-dress')->assertOk()
            ->assertSee('<title>عنوان مخصص</title>', false)
            ->assertSee('max-image-preview:large', false);

        $en = $this->get('/en/product/royal-dress')->assertOk()
            ->assertSee('<title>Custom English Title</title>', false)
            ->assertSee('<meta name="description" content="Custom English meta description.">', false)
            ->assertSee('<meta property="product:price:amount" content="49.500">', false)
            ->assertSee('English description of the dress');

        $this->assertSame('English description of the dress', $this->jsonLd($en)['Product']['description']);
    }

    public function test_structured_data_is_rich_result_ready(): void
    {
        $this->product(['stock_status' => 'backorder', 'brand' => 'Lozan Couture', 'sku' => 'RD-01', 'gtin' => '12345678', 'material' => 'Chiffon', 'offer_price' => 39.5]);

        $graph = $this->jsonLd($this->get('/en/product/royal-dress')->assertOk());
        $product = $graph['Product'];

        $this->assertSame('RD-01', $product['sku']);
        $this->assertSame('12345678', $product['gtin']);
        $this->assertSame('Lozan Couture', $product['brand']['name']);
        $this->assertSame('Chiffon', $product['material']);
        $this->assertSame('https://schema.org/BackOrder', $product['offers']['availability']);
        $this->assertSame('39.500', $product['offers']['price']);
        $this->assertSame('https://schema.org/StrikethroughPrice', $product['offers']['priceSpecification']['priceType']);
        $this->assertSame('DAY', $product['offers']['shippingDetails']['deliveryTime']['transitTime']['unitCode']);
        $this->assertArrayNotHasKey('mpn', $product);
        $this->assertCount(3, $graph['BreadcrumbList']['itemListElement']);
    }

    public function test_noindex_product_is_hidden_from_search_but_still_viewable(): void
    {
        $this->product(['seo_noindex' => true]);

        $this->get('/product/royal-dress')->assertOk()->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        $this->assertStringNotContainsString('royal-dress', app(SitemapService::class)->render());
    }

    public function test_editing_keeps_the_url_unless_slug_is_changed_and_old_url_redirects(): void
    {
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->formData(['name_en' => 'Renamed Dress']))
            ->assertRedirect(route('admin.products'));
        $this->assertSame('royal-dress', $product->fresh()->slug);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->formData(['slug' => 'Royal Gown 2026']));
        $this->assertSame('royal-gown-2026', $product->fresh()->slug);

        $this->get('/en/product/royal-dress')->assertStatus(301)->assertRedirect(url('/en/product/royal-gown-2026'));
        $this->get('/product/royal-gown-2026')->assertOk();
    }

    public function test_slug_and_gtin_are_validated(): void
    {
        $this->product(['slug' => 'taken-slug']);
        $other = $this->product(['slug' => 'other-dress']);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.products.update', $other), $this->formData(['slug' => 'taken-slug', 'gtin' => '123']))
            ->assertSessionHasErrors(['slug', 'gtin']);
        $this->assertSame('other-dress', $other->fresh()->slug);
    }

    public function test_new_product_gets_generated_slug_and_form_shows_advanced_section(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()
            ->assertSee('class="pf-advanced"', false)->assertSee('name="seo_title_en"', false);

        $this->actingAs($admin)->post(route('admin.products.store'), $this->formData(['name_en' => 'Pearl Midi Dress', 'material' => 'Satin']));
        $product = Product::query()->sole();
        $this->assertSame('pearl-midi-dress', $product->slug);
        $this->assertSame('Satin', $product->material);
        $this->assertNull($product->seo_title_ar);

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('value="Satin"', false);
    }
}
