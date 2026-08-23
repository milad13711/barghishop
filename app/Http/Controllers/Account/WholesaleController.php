<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PriceTier;
use Illuminate\Http\Request;

class WholesaleController extends Controller
{
    public function form()
    {
        return view('account.wholesale', [
            'seo'      => ['title' => 'درخواست همکاری | '.config('shop.name')],
            'customer' => auth('customer')->user(),
            'tiers'    => PriceTier::where('is_wholesale', true)->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'company'       => ['required', 'string', 'max:150'],
            'national_id'   => ['nullable', 'string', 'max:20'],
            'economic_code' => ['nullable', 'string', 'max:30'],
            'price_tier_id' => ['required', 'exists:price_tiers,id'],
            'note'          => ['nullable', 'string', 'max:1000'],
        ]);

        $tier = PriceTier::findOrFail($data['price_tier_id']);

        abort_unless($tier->is_wholesale, 422);

        auth('customer')->user()->update([
            'name'                   => $data['name'],
            'company'                => $data['company'],
            'national_id'            => $data['national_id'] ?? null,
            'economic_code'          => $data['economic_code'] ?? null,
            'price_tier_id'          => $tier->id,
            'wholesale_note'         => $data['note'] ?? null,
            'wholesale_status'       => Customer::WHOLESALE_PENDING,
            'wholesale_requested_at' => now(),
        ]);

        return redirect()->route('account.dashboard')
            ->with('success', 'درخواست همکاری شما ثبت شد و پس از بررسی از طریق پیامک اطلاع‌رسانی می‌شود.');
    }
}
