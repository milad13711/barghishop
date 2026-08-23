<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        return view('account.orders', [
            'seo'    => ['title' => 'سفارش‌های من | '.config('shop.name')],
            'orders' => auth('customer')->user()->orders()->latest()->paginate(15),
        ]);
    }

    public function show(Order $order)
    {
        abort_unless($order->customer_id === auth('customer')->id(), 403);

        $order->load(['items.product.media', 'statusLogs', 'shippingMethod', 'transactions']);

        return view('account.order-show', [
            'seo'   => ['title' => 'سفارش '.$order->code.' | '.config('shop.name')],
            'order' => $order,
        ]);
    }
}
