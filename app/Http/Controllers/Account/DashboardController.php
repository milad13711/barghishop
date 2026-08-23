<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyLevel;
use App\Models\Order;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $customer = auth('customer')->user();

        $nextLevel = LoyaltyLevel::where('min_points', '>', $customer->points)
            ->orderBy('min_points')->first();

        return view('account.dashboard', [
            'seo'       => ['title' => 'داشبورد من | '.config('shop.name')],
            'customer'  => $customer,
            'orders'    => $customer->orders()->latest()->limit(5)->get(),
            'stats'     => [
                'orders_count' => $customer->orders()->count(),
                'in_progress'  => $customer->orders()
                    ->whereIn('status', [Order::PAID, Order::PROCESSING, Order::SHIPPED])->count(),
                'delivered'    => $customer->orders()->where('status', Order::DELIVERED)->count(),
            ],
            'nextLevel' => $nextLevel,
        ]);
    }
}
