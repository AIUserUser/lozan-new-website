<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\SeoService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(CartService $cart, SeoService $seo)
    {
        return view('store.cart', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'seo' => $seo->page('cart', 'cart'),
        ]);
    }

    public function update(Request $request, CartService $cart)
    {
        $cart->updateQty((string) $request->input('line_id'), (int) $request->input('qty', 1));

        return back();
    }

    public function remove(Request $request, CartService $cart)
    {
        $cart->remove((string) $request->input('line_id'));

        return back();
    }
}
