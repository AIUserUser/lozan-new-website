<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use App\Services\SeoService;

class HomeController extends Controller
{
    public function __invoke(CatalogService $catalog, SeoService $seo)
    {
        $products = $catalog->published();
        $color = request('color');
        $size = request('size');
        $filtered = $catalog->filter($products, 'all', $color, $size);

        return view('store.home', [
            'products' => $filtered,
            'allProducts' => $products,
            'colorOptions' => $catalog->colorOptions($products),
            'sizeOptions' => $catalog->sizeOptions($products),
            'selectedColor' => $color,
            'selectedSize' => $size,
            'seo' => $seo->page('home', ''),
        ]);
    }
}
