<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SeoService;
use Illuminate\Http\Request;

class OrderConfirmationController extends Controller
{
    public function __invoke(Request $request, SeoService $seo)
    {
        $order = null;
        $id = $request->query('id');
        $phone = (string) $request->input('phone', $request->query('phone', ''));

        if ($id && $phone !== '') {
            $order = Order::query()->with('items')->find($id);
            if ($order) {
                $a = preg_replace('/\D/', '', $order->phone) ?? '';
                $b = preg_replace('/\D/', '', $phone) ?? '';
                if ($a === '' || $a !== $b) {
                    $order = null;
                    $mismatch = true;
                }
            }
        }

        return view('store.thanks', [
            'order' => $order ?? null,
            'orderId' => $id,
            'mismatch' => $mismatch ?? false,
            'seo' => $seo->page('thanks', 'order-confirmation'),
        ]);
    }
}
