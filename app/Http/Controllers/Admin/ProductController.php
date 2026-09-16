<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\ProductSlugRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()->with('images')->latest()->get();

        return view('admin.products', ['products' => $products]);
    }

    public function create()
    {
        return view('admin.product-form', ['product' => null]);
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'colors', 'sizes']);

        return view('admin.product-form', ['product' => $product]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new Product);
    }

    public function update(Request $request, Product $product)
    {
        return $this->save($request, $product);
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
            if (! str_starts_with($image->path, 'http')) {
                Storage::disk('public')->delete($image->path);
            }
        }
        $product->delete();

        return redirect()->route('admin.products');
    }

    private function save(Request $request, Product $product)
    {
        $request->merge(['slug' => Str::slug((string) $request->input('slug', ''))]);
        $data = $request->validate([
            'name' => 'required|string|min:2|max:191',
            'name_en' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'category' => 'required|in:gown,party,all',
            'published' => 'sometimes|boolean',
            'stock_status' => 'required|in:in_stock,backorder,unavailable',
            'cover_index' => 'nullable|integer|min:0',
            // Advanced: SEO & rich results
            'slug' => [
                'nullable', 'string', 'max:191',
                Rule::unique('products', 'slug')->ignore($product->id),
                Rule::unique('product_slug_redirects', 'slug')->where(fn ($q) => $q->where('product_id', '!=', $product->id ?? 0)),
            ],
            'seo_title_ar' => 'nullable|string|max:191',
            'seo_title_en' => 'nullable|string|max:191',
            'seo_description_ar' => 'nullable|string|max:320',
            'seo_description_en' => 'nullable|string|max:320',
            'seo_noindex' => 'sometimes|boolean',
            'brand' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:64',
            'gtin' => ['nullable', 'regex:/^(\d{8}|\d{12,14})$/'],
            'mpn' => 'nullable|string|max:70',
            'material' => 'nullable|string|max:100',
        ], [
            'slug.unique' => lozan_t('admin.form.seo.errSlugTaken'),
            'gtin.regex' => lozan_t('admin.form.seo.errGtin'),
        ]);

        if (isset($data['offer_price']) && $data['offer_price'] !== null && $data['offer_price'] >= $data['price']) {
            return back()->withInput()->withErrors(['offer_price' => lozan_t('admin.form.errOfferTooHigh')]);
        }

        $nullIfBlank = fn (?string $value) => ($value = trim((string) $value)) === '' ? null : $value;

        DB::transaction(function () use ($request, $product, $data, $nullIfBlank) {
            $oldSlug = $product->exists ? $product->slug : null;
            // Keep the existing URL unless the admin sets a new slug; only new products get a generated one.
            $slug = $data['slug'] ?: ($oldSlug ?: Product::uniqueSlug($data['name_en'] ?? '', $data['name']));

            $product->fill([
                'name' => $data['name'],
                'name_en' => $data['name_en'] ?? '',
                'description' => $data['description'] ?? '',
                'description_en' => $nullIfBlank($data['description_en'] ?? null),
                'price' => $data['price'],
                'offer_price' => ($data['offer_price'] ?? null) !== null && $data['offer_price'] !== '' ? $data['offer_price'] : null,
                'category' => $data['category'],
                'published' => $request->boolean('published'),
                'stock_status' => $data['stock_status'],
                'cover_index' => (int) ($data['cover_index'] ?? 0),
                'slug' => $slug,
                'seo_title_ar' => $nullIfBlank($data['seo_title_ar'] ?? null),
                'seo_title_en' => $nullIfBlank($data['seo_title_en'] ?? null),
                'seo_description_ar' => $nullIfBlank($data['seo_description_ar'] ?? null),
                'seo_description_en' => $nullIfBlank($data['seo_description_en'] ?? null),
                'seo_noindex' => $request->boolean('seo_noindex'),
                'brand' => $nullIfBlank($data['brand'] ?? null),
                'sku' => $nullIfBlank($data['sku'] ?? null),
                'gtin' => $nullIfBlank($data['gtin'] ?? null),
                'mpn' => $nullIfBlank($data['mpn'] ?? null),
                'material' => $nullIfBlank($data['material'] ?? null),
            ]);
            $product->save();

            if ($oldSlug && $oldSlug !== $slug) {
                ProductSlugRedirect::query()->where('slug', $slug)->delete();
                ProductSlugRedirect::query()->updateOrCreate(['slug' => $oldSlug], ['product_id' => $product->id]);
            }

            $this->syncColors($request, $product);
            $this->syncSizes($request, $product);
            $this->syncImages($request, $product);
        });

        return redirect()->route('admin.products');
    }

    private function syncColors(Request $request, Product $product): void
    {
        $product->colors()->delete();
        $ars = $request->input('color_ar', []);
        $ens = $request->input('color_en', []);
        $hexes = $request->input('color_hex', []);
        foreach ($ars as $i => $ar) {
            $ar = trim((string) $ar);
            if ($ar === '') {
                continue;
            }
            ProductColor::query()->create([
                'product_id' => $product->id,
                'name_ar' => $ar,
                'name_en' => trim((string) ($ens[$i] ?? '')),
                'hex' => trim((string) ($hexes[$i] ?? '')),
                'sort_order' => $i,
            ]);
        }
    }

    private function syncSizes(Request $request, Product $product): void
    {
        $product->sizes()->delete();
        foreach ($request->input('sizes', []) as $i => $size) {
            $size = trim((string) $size);
            $size = preg_replace('/^eu\s*/i', '', $size) ?? $size;
            if ($size === '') {
                continue;
            }
            ProductSize::query()->create([
                'product_id' => $product->id,
                'size' => $size,
                'sort_order' => $i,
            ]);
        }
    }

    private function syncImages(Request $request, Product $product): void
    {
        $keep = collect($request->input('keep_images', []))->map(fn ($id) => (int) $id)->all();
        foreach ($product->images as $image) {
            if (! in_array($image->id, $keep, true)) {
                if (! str_starts_with($image->path, 'http')) {
                    Storage::disk('public')->delete($image->path);
                }
                $image->delete();
            }
        }
        if ($request->hasFile('images')) {
            $sort = $product->images()->count();
            foreach ($request->file('images') as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $path = $file->store('products/'.$product->id, 'public');
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'sort_order' => $sort++,
                ]);
            }
        }
        $product->refresh();
        $count = $product->images()->count();
        if ($count === 0) {
            // allow save without images only when editing existing with leftover? plan required at least one for new
        }
        $product->cover_index = min((int) $product->cover_index, max(0, $count - 1));
        $product->save();
    }
}
