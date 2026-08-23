<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyLevel;

class LoyaltyController extends Controller
{
    public function __invoke()
    {
        $customer = auth('customer')->user();

        return view('account.loyalty', [
            'seo'      => ['title' => 'باشگاه مشتریان | '.config('shop.name')],
            'customer' => $customer,
            'levels'   => LoyaltyLevel::orderBy('min_points')->get(),
            'entries'  => $customer->loyaltyEntries()->latest()->paginate(20),
        ]);
    }
}
