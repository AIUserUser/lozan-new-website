<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $data = $request->validate([
            'name' => 'required|string|min:2|max:191',
            'name_en' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'category' => 'required|in:gown,party,all',
            'published' => 'sometimes|boolean',
            'stock_status' => 'required|in:in_stock,backorder,unavailable',
            'cover_index' => 'nullable|integer|min:0',
        ]);

        if (isset($data['offer_price']) && $data['offer_price'] !== null && $data['offer_price'] >= $data['price']) {
            return back()->withInput()->withErrors(['offer_price' => lozan_t('admin.form.errOfferTooHigh')]);
        }

        $product->fill([
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?? '',
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'offer_price' => $data['offer_price'] !== null && $data['offer_price'] !== '' ? $data['offer_price'] : null,
            'category' => $data['category'],
            'published' => $request->boolean('published'),
            'stock_status' => $data['stock_status'],
            'cover_index' => (int) ($data['cover_index'] ?? 0),
            'slug' => Product::uniqueSlug($data['name_en'] ?? '', $data['name'], $product->id),
        ]);
        $product->save();

        $this->syncColors($request, $product);
        $this->syncSizes($request, $product);
        $this->syncImages($request, $product);

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
