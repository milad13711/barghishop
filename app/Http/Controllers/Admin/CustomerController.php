<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PriceTier;
use App\Services\Sms\SmsManager;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with(['tier', 'loyaltyLevel'])
            ->withCount('orders')
            ->when($request->query('status'), fn ($q, $s) => $q->where('wholesale_status', $s))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) =>
                $w->where('mobile', 'like', "%$term%")
                  ->orWhere('name', 'like', "%$term%")
                  ->orWhere('company', 'like', "%$term%")))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers'     => $customers,
            'pendingCount'  => Customer::where('wholesale_status', Customer::WHOLESALE_PENDING)->count(),
            'tiers'         => PriceTier::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer, SmsManager $sms)
    {
        $data = $request->validate([
            'price_tier_id'    => ['nullable', 'exists:price_tiers,id'],
            'wholesale_status' => ['required', 'in:none,pending,approved,rejected'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $wasApproved = $customer->isWholesaler();

        $customer->update([
            'price_tier_id'    => $data['price_tier_id'] ?: $customer->price_tier_id,
            'wholesale_status' => $data['wholesale_status'],
            'is_active'        => $request->boolean('is_active'),
        ]);

        if (! $wasApproved && $customer->refresh()->isWholesaler()) {
            $sms->sendPattern($customer->mobile, 'wholesale_ok', [
                'name' => $customer->name ?: 'همکار گرامی',
                'tier' => $customer->effectiveTier()->name,
            ], $customer);
        }

        return back()->with('success', 'اطلاعات مشتری به‌روزرسانی شد.');
    }
}
