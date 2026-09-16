<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes): Product
    {
        return Product::query()->create($attributes + [
            'name' => 'فستان',
            'price' => 49.5,
            'category' => 'gown',
            'published' => true,
            'stock_status' => 'in_stock',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $royal = $this->product([
            'slug' => 'royal-dress', 'name' => 'فستان ملكي', 'name_en' => 'Royal Dress',
            'description' => 'وصف عربي', 'description_en' => 'Soft chiffon with a long cape.',
            'offer_price' => 39.5, 'material' => 'Chiffon',
        ]);
        $royal->colors()->create(['name_ar' => 'كحلي', 'name_en' => 'Navy', 'sort_order' => 0]);
        $royal->sizes()->create(['size' => '38', 'sort_order' => 0]);
        $royal->images()->create(['path' => 'products/1/00.jpg', 'sort_order' => 0]);

        $this->product(['slug' => 'draft-dress', 'name_en' => 'Draft Dress', 'published' => false]);
        $this->product(['slug' => 'hidden-dress', 'name_en' => 'Hidden Dress', 'seo_noindex' => true]);
    }

    public function test_llms_txt_lists_the_store_and_indexable_products(): void
    {
        $response = $this->get('/llms.txt');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeaderMissing('Set-Cookie');
        $body = $response->getContent();

        $this->assertStringStartsWith("# Lozan (لوذان)\n\n> ", $body);
        $this->assertStringContainsString('## Products', $body);
        $this->assertStringContainsString('- [Royal Dress]('.url('/en/product/royal-dress').'): 39.500 KWD (on sale, regular 49.500 KWD) · Gown · In stock · Colors: Navy (كحلي) · Sizes: EU 38 · Soft chiffon with a long cape.', $body);
        $this->assertStringContainsString('('.url('/llms-full.txt').')', $body);
        $this->assertStringNotContainsString('Draft Dress', $body);
        $this->assertStringNotContainsString('Hidden Dress', $body);
    }

    public function test_llms_full_txt_has_complete_bilingual_details(): void
    {
        $body = $this->get('/llms-full.txt')->assertOk()->assertHeaderMissing('Set-Cookie')->getContent();

        $this->assertStringContainsString('### Royal Dress', $body);
        $this->assertStringContainsString('- Arabic name: فستان ملكي', $body);
        $this->assertStringContainsString('- Material: Chiffon', $body);
        $this->assertStringContainsString('- Page (Arabic): '.url('/product/royal-dress'), $body);
        $this->assertStringContainsString('- Image 1: ', $body);
        $this->assertStringContainsString('الوصف: وصف عربي', $body);
        $this->assertStringNotContainsString('Hidden Dress', $body);
    }
}
