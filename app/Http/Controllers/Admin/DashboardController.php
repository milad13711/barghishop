<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $paidStatuses = [Order::PAID, Order::PROCESSING, Order::SHIPPED, Order::DELIVERED];

        return view('admin.dashboard', [
            'stats' => [
                'today_orders'  => Order::whereDate('created_at', today())->count(),
                'today_revenue' => (int) Order::whereIn('status', $paidStatuses)
                                        ->whereDate('created_at', today())->sum('grand_total'),
                'month_revenue' => (int) Order::whereIn('status', $paidStatuses)
                                        ->where('created_at', '>=', now()->subDays(30))->sum('grand_total'),
                'pending'       => Order::where('status', Order::PENDING_PAYMENT)->count(),
                'processing'    => Order::whereIn('status', [Order::PAID, Order::PROCESSING])->count(),
                'customers'     => Customer::count(),
                'low_stock'     => Product::where('track_stock', true)->where('stock', '<=', 3)->count(),
                'wholesale_requests' => Customer::where('wholesale_status', Customer::WHOLESALE_PENDING)->count(),
                'unread_messages'    => ContactMessage::where('status', 'new')->count(),
            ],
            'recentOrders' => Order::with('customer')->latest()->limit(8)->get(),
            'lowStock'     => Product::where('track_stock', true)->where('stock', '<=', 3)
                                ->orderBy('stock')->limit(8)->get(),
        ]);
    }
}
