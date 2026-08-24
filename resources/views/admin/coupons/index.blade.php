@extends('admin.layouts.app')
@section('heading', 'کدهای تخفیف')

@section('content')
<div class="grid gap-5 lg:grid-cols-[1fr_340px]">
    <div class="card overflow-x-auto">
        <table class="w-full min-w-[620px] text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="p-4 font-medium">کد</th>
                    <th class="p-4 font-medium">مقدار</th>
                    <th class="p-4 font-medium">حداقل سبد</th>
                    <th class="p-4 font-medium">مصرف</th>
                    <th class="p-4 font-medium">انقضا</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse($coupons as $coupon)
                    <tr>
                        <td class="p-4">
                            <div class="font-bold text-navy-900" dir="ltr">{{ $coupon->code }}</div>
                            @if($coupon->title)<div class="mt-0.5 text-xs text-navy-400">{{ $coupon->title }}</div>@endif
                        </td>
                        <td class="p-4 text-navy-800 nums-fa">
                            @if($coupon->type === 'percent')
                                {{ \App\Support\Digits::toPersian((string) $coupon->value) }}٪
                            @elseif($coupon->type === 'fixed')
                                {{ \App\Support\Money::format($coupon->value, false) }} تومان
                            @else
                                ارسال رایگان
                            @endif
                        </td>
                        <td class="p-4 text-xs text-navy-500 nums-fa">
                            {{ $coupon->min_total ? \App\Support\Money::format($coupon->min_total, false) : '—' }}
                        </td>
                        <td class="p-4 text-xs text-navy-500 nums-fa">
                            {{ \App\Support\Digits::toPersian((string) $coupon->used_count) }}
                            @if($coupon->usage_limit)/ {{ \App\Support\Digits::toPersian((string) $coupon->usage_limit) }}@endif
                        </td>
                        <td class="p-4 text-xs text-navy-500 nums-fa">
                            {{ $coupon->expires_at ? \App\Support\Digits::toPersian($coupon->expires_at->format('Y/m/d')) : 'بدون انقضا' }}
                        </td>
                        <td class="p-4 text-left">
                            <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="post" onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-rose-600">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-12 text-center text-navy-400">کد تخفیفی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card h-fit space-y-4 p-5">
        <h2 class="text-sm font-bold text-navy-900">کد تخفیف جدید</h2>

        <form action="{{ route('admin.coupons.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">کد *</label>
                <input type="text" name="code" dir="ltr" class="input !py-2.5" placeholder="NOWRUZ1405" required>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">عنوان</label>
                <input type="text" name="title" class="input !py-2.5" placeholder="جشنواره نوروزی">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">نوع</label>
                <select name="type" class="input !py-2.5">
                    <option value="percent">درصدی</option>
                    <option value="fixed">مبلغ ثابت (تومان)</option>
                    <option value="free_shipping">ارسال رایگان</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">مقدار</label>
                <input type="number" name="value" dir="ltr" class="input !py-2.5" placeholder="۱۰ برای درصد، یا مبلغ به تومان">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">سقف تخفیف (تومان)</label>
                <input type="number" name="max_discount" dir="ltr" class="input !py-2.5">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">حداقل مبلغ سبد (تومان)</label>
                <input type="number" name="min_total" dir="ltr" class="input !py-2.5">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">سقف کل</label>
                    <input type="number" name="usage_limit" dir="ltr" class="input !py-2.5">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">سقف هر مشتری</label>
                    <input type="number" name="usage_limit_per_customer" value="1" dir="ltr" class="input !py-2.5">
                </div>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">تاریخ انقضا</label>
                <input type="date" name="expires_at" dir="ltr" class="input !py-2.5">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">محدود به سطوح (خالی = همه)</label>
                <div class="space-y-1.5">
                    @foreach($tiers as $tier)
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-navy-600">
                            <input type="checkbox" name="tier_scope[]" value="{{ $tier->id }}"
                                   class="size-4 rounded border-navy-200 text-electric-500">
                            {{ $tier->name }}
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="btn-primary w-full !py-2.5 !text-xs">ساخت کد</button>
        </form>
    </div>
</div>
@endsection
