<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\SeoService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(string $slug, SeoService $seo)
    {
        $product = Product::query()
            ->published()
            ->where('slug', $slug)
            ->with(['images', 'colors', 'sizes'])
            ->first();

        if (! $product) {
            $legacy = Product::query()->where('legacy_id', $slug)->first();
            if ($legacy) {
                return redirect(product_url($legacy), 301);
            }
            abort(404);
        }

        $seoData = $seo->product($product);

        return view('store.product', [
            'product' => $product,
            'seo' => $seoData,
            'jsonLd' => $seo->productJsonLd($product, $seoData),
        ]);
    }

    public function addToCart(Request $request, string $slug, CartService $cart)
    {
        $product = Product::query()->published()->where('slug', $slug)->with(['colors', 'sizes', 'images'])->firstOrFail();
        $qty = (int) $request->input('qty', 1);
        $backorder = $request->boolean('backorder');

        if ($product->isUnavailable()) {
            return back()->withErrors(['cart' => lozan_t('product.unavailableNote')]);
        }
        if ($backorder && ! $product->canBackorder()) {
            return back();
        }
        if (! $backorder && ! $product->isInStock()) {
            return back();
        }

        $color = null;
        if ($product->colors->isNotEmpty()) {
            $key = (string) $request->input('color');
            $color = collect($product->colorArrays())->first(fn ($c) => color_key($c) === $key);
            if (! $color) {
                return back()->withErrors(['color' => lozan_t('product.colorLabel')]);
            }
        }
        $size = null;
        if ($product->sizes->isNotEmpty()) {
            $size = (string) $request->input('size');
            $allowed = $product->sizeValues();
            if (! in_array($size, $allowed, true)) {
                return back()->withErrors(['size' => lozan_t('product.errPickSize')]);
            }
        }

        $cart->add($product, $qty, $color, $size, $backorder);

        return back()->with('added', $backorder ? 'backorder' : 'ok');
    }
}
