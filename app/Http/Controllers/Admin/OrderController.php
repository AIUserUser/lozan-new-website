<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::query()->with('items')->latest()->get();

        return view('admin.orders', ['orders' => $orders]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,contacted,done',
        ]);
        $order->update(['status' => $data['status']]);

        return back();
    }
}
