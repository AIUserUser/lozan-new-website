<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use App\Services\SeoService;

class ShopController extends Controller
{
    public function __invoke(CatalogService $catalog, SeoService $seo)
    {
        $products = $catalog->published();
        $category = request('category', 'all');
        $color = request('color');
        $size = request('size');
        $filtered = $catalog->filter($products, $category, $color, $size);

        return view('store.shop', [
            'products' => $filtered,
            'allProducts' => $products,
            'category' => $category,
            'colorOptions' => $catalog->colorOptions($products),
            'sizeOptions' => $catalog->sizeOptions($products),
            'selectedColor' => $color,
            'selectedSize' => $size,
            'seo' => $seo->page('shop', 'shop'),
        ]);
    }
}
