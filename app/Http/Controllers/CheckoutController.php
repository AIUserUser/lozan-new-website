<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\SeoService;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show(CartService $cart, SeoService $seo)
    {
        if ($cart->count() === 0) {
            return view('store.checkout', [
                'items' => [],
                'subtotal' => 0,
                'seo' => $seo->page('checkout', 'checkout'),
            ]);
        }

        return view('store.checkout', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'seo' => $seo->page('checkout', 'checkout'),
        ]);
    }

    public function store(Request $request, CartService $cart, TelegramNotifier $telegram)
    {
        if ($cart->count() === 0) {
            return back()->withErrors(['cart' => lozan_t('checkout.bagEmpty')]);
        }

        $country = gcc_country($request->input('country', 'KW'));
        $phoneRaw = (string) $request->input('phone');
        $phoneConfirm = (string) $request->input('phone_confirm');
        $local = local_phone_digits($phoneRaw, $country);
        $local2 = local_phone_digits($phoneConfirm, $country);

        $request->validate([
            'name' => 'required|string|max:120',
            'country' => 'required|string|max:2',
        ]);

        if ($local === '') {
            return back()->withInput()->withErrors(['phone' => lozan_t('checkout.errPhoneRequired')]);
        }
        if (strlen($local) !== $country['length']) {
            return back()->withInput()->withErrors(['phone' => lozan_t('checkout.errPhoneLength', [
                'country' => app()->getLocale() === 'en' ? $country['en'] : $country['ar'],
                'expected' => $country['length'],
                'actual' => strlen($local),
            ])]);
        }
        if ($local !== $local2) {
            return back()->withInput()->withErrors(['phone_confirm' => lozan_t('checkout.errPhoneMatch')]);
        }

        $wantsDelivery = $request->boolean('wants_delivery');
        $address = trim((string) $request->input('address', ''));
        if ($wantsDelivery && $address === '') {
            return back()->withInput()->withErrors(['address' => lozan_t('checkout.errAddress')]);
        }
        if (mb_strlen($address) > 800) {
            return back()->withInput()->withErrors(['address' => lozan_t('checkout.errAddress')]);
        }

        $phone = format_e164($phoneRaw, $country);
        $subtotal = $cart->subtotal();

        $order = DB::transaction(function () use ($request, $cart, $phone, $wantsDelivery, $address, $subtotal) {
            $order = Order::query()->create([
                'legacy_id' => (string) Str::uuid(),
                'customer_name' => trim((string) $request->input('name')),
                'phone' => $phone,
                'address' => $address,
                'wants_delivery' => $wantsDelivery,
                'subtotal' => $subtotal,
                'status' => 'pending',
            ]);
            foreach ($cart->items() as $item) {
                $color = is_array($item['color'] ?? null) ? $item['color'] : [];
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'] ?? null,
                    'name_ar' => $item['name_ar'] ?? '',
                    'name_en' => $item['name_en'] ?? '',
                    'color_ar' => $color['ar'] ?? null,
                    'color_en' => $color['en'] ?? null,
                    'color_hex' => $color['hex'] ?? null,
                    'size' => $item['size'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['price'] ?? 0,
                    'backorder' => ! empty($item['backorder']),
                    'image_path' => $item['image_url'] ?? null,
                ]);
            }

            return $order;
        });

        $cart->clear();
        try {
            $telegram->notifyOrder($order->fresh('items'));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect(locale_url('order-confirmation').'?id='.$order->id);
    }
}
