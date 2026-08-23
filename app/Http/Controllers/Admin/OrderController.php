<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('customer')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), function ($q, $term) {
                $q->where(fn ($w) => $w->where('code', 'like', "%$term%")
                    ->orWhereHas('customer', fn ($c) => $c->where('mobile', 'like', "%$term%")
                        ->orWhere('name', 'like', "%$term%")));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'counts' => Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'statusLogs', 'customer', 'transactions', 'shippingMethod']);

        return view('admin.orders.show', [
            'order'    => $order,
            'nextList' => Order::TRANSITIONS[$order->status] ?? [],
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderStatusService $service)
    {
        $data = $request->validate([
            'status'        => ['required', 'string'],
            'tracking_code' => ['nullable', 'string', 'max:60'],
            'note'          => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->transition(
                $order,
                $data['status'],
                note: $data['note'] ?? null,
                actorType: 'admin',
                actorId: auth()->id(),
                extra: array_filter(['tracking_code' => $data['tracking_code'] ?? null]),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'وضعیت سفارش به‌روزرسانی شد و پیامک مربوطه ارسال گردید.');
    }
}
