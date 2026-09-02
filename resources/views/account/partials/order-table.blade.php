@php
    $labels = [
        'pending_payment' => ['در انتظار پرداخت', 'bg-amber-50 text-amber-700'],
        'paid'            => ['پرداخت‌شده', 'bg-emerald-50 text-emerald-700'],
        'processing'      => ['در حال آماده‌سازی', 'bg-electric-50 text-electric-700'],
        'shipped'         => ['ارسال‌شده', 'bg-indigo-50 text-indigo-700'],
        'delivered'       => ['تحویل‌شده', 'bg-emerald-50 text-emerald-700'],
        'cancelled'       => ['لغو شده', 'bg-slate-100 text-slate-600'],
        'refunded'        => ['مرجوع شده', 'bg-rose-50 text-rose-700'],
    ];
@endphp

@if($orders->isEmpty())
    <div class="grid place-items-center gap-3 py-12 text-center">
        <p class="text-sm text-navy-400">هنوز سفارشی ثبت نکرده‌اید.</p>
        <a href="{{ route('shop.index') }}" class="btn-primary !py-2.5 !text-xs">شروع خرید</a>
    </div>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="pb-3 font-medium">شماره سفارش</th>
                    <th class="pb-3 font-medium">تاریخ</th>
                    <th class="pb-3 font-medium">مبلغ</th>
                    <th class="pb-3 font-medium">وضعیت</th>
                    <th class="pb-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @foreach($orders as $order)
                    @php [$label, $classes] = $labels[$order->status] ?? [$order->status, 'bg-slate-100 text-slate-600']; @endphp
                    <tr>
                        <td class="py-3 font-medium text-navy-900 nums-fa" dir="ltr">{{ $order->code }}</td>
                        <td class="py-3 text-navy-500 nums-fa">@jd($order->created_at)</td>
                        <td class="py-3 font-medium text-navy-900 nums-fa">{{ \App\Support\Money::format($order->grand_total) }}</td>
                        <td class="py-3"><span class="badge {{ $classes }}">{{ $label }}</span></td>
                        <td class="py-3 text-left">
                            <a href="{{ route('account.orders.show', $order) }}" class="text-xs font-semibold text-electric-600">جزئیات</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
