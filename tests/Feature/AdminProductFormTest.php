<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::query()->create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass', 'is_admin' => true]);
    }

    private function data(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'فستان',
            'name_en' => 'Dress',
            'price' => '49.5',
            'category' => 'gown',
            'published' => '1',
            'stock_status' => 'in_stock',
        ];
    }

    private function image(string $name = 'photo.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 600, 800);
    }

    public function test_cover_can_be_a_new_upload(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->data([
            'images' => [$this->image('a.jpg'), $this->image('b.jpg'), $this->image('c.jpg')],
            'cover' => 'new:2',
        ]))->assertRedirect(route('admin.products'));

        $product = Product::query()->with('images')->sole();
        $this->assertCount(3, $product->images);
        $this->assertSame($product->images[2]->id, $product->coverImage()->id);
    }

    public function test_cover_stays_on_the_chosen_photo_when_an_earlier_photo_is_removed(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->data([
            'images' => [$this->image('a.jpg'), $this->image('b.jpg'), $this->image('c.jpg')],
        ]));
        $product = Product::query()->with('images')->sole();
        [$first, $second, $third] = $product->images->all();

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), $this->data([
            'keep_images' => [$second->id, $third->id],
            'cover' => 'existing:'.$third->id,
            'images' => [$this->image('d.jpg')],
        ]))->assertRedirect(route('admin.products'));

        $product->refresh()->load('images');
        $this->assertCount(3, $product->images);
        $this->assertFalse($product->images->contains('id', $first->id));
        $this->assertSame($third->id, $product->coverImage()->id);
        Storage::disk('public')->assertMissing($first->path);
    }

    public function test_visible_product_needs_a_photo_but_hidden_draft_does_not(): void
    {
        $this->actingAs($this->admin)->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->data())
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('images');
        $this->assertSame(0, Product::query()->count());

        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->data(['published' => '0']))
            ->assertRedirect(route('admin.products'));
        $this->assertFalse(Product::query()->sole()->published);
    }

    public function test_custom_sizes_and_colors_are_saved(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->data([
            'images' => [$this->image()],
            'sizes' => ['38', 'EU 50', ''],
            'color_ar' => ['كحلي', ''],
            'color_en' => ['Navy', ''],
            'color_hex' => ['#1d2a4d', '#888888'],
        ]));

        $product = Product::query()->with(['sizes', 'colors'])->sole();
        $this->assertSame(['38', '50'], $product->sizeValues());
        $this->assertSame([['ar' => 'كحلي', 'en' => 'Navy', 'hex' => '#1d2a4d']], $product->colorArrays());

        $this->actingAs($this->admin)->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('value="50" checked', false)
            ->assertSee('value="كحلي"', false)
            ->assertSee('data-photo="existing:', false);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->data([
            'images' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')],
        ]))->assertSessionHasErrors('images.0');
    }
}
