<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\PriceTier;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::withCount('usages')->latest()->paginate(25),
            'tiers'   => PriceTier::orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->data($request));

        return back()->with('success', 'کد تخفیف ساخته شد.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->data($request, $coupon));

        return back()->with('success', 'کد تخفیف به‌روزرسانی شد.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('success', 'کد تخفیف حذف شد.');
    }

    protected function data(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:60', 'unique:coupons,code,'.($coupon->id ?? 'NULL')],
            'title'        => ['nullable', 'string', 'max:150'],
            'type'         => ['required', 'in:percent,fixed,free_shipping'],
            'value'        => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_total'    => ['nullable', 'numeric', 'min:0'],
            'usage_limit'  => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'expires_at'   => ['nullable', 'date'],
            'tier_scope'   => ['nullable', 'array'],
        ]);

        // مبالغ ثابت به تومان وارد و به ریال ذخیره می‌شوند؛ درصد بدون تبدیل می‌ماند.
        foreach (['max_discount', 'min_total'] as $field) {
            $data[$field] = ! empty($data[$field]) ? Money::fromToman((int) $data[$field]) : null;
        }

        $data['value'] = $data['type'] === Coupon::FIXED
            ? Money::fromToman((int) ($data['value'] ?? 0))
            : (int) ($data['value'] ?? 0);

        $data['tier_scope'] = ! empty($data['tier_scope'])
            ? array_map('intval', $data['tier_scope'])
            : null;

        $data['is_active'] = $request->boolean('is_active', true);

        return Arr::except($data, []);
    }
}
