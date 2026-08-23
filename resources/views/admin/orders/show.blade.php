@extends('admin.layouts.app')
@section('heading', 'سفارش '.$order->code)

@section('content')
@php
    $labels = [
        'paid' => 'ثبت پرداخت دستی', 'processing' => 'شروع آماده‌سازی',
        'shipped' => 'ارسال شد', 'delivered' => 'تحویل داده شد',
        'cancelled' => 'لغو سفارش', 'refunded' => 'مرجوع و بازگشت وجه',
    ];
@endphp

<div class="grid gap-5 lg:grid-cols-[1fr_340px]">
    <div class="space-y-5">
        <div class="card divide-y divide-navy-50">
            @foreach($order->items as $item)
                <div class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-navy-900">{{ $item->name_snapshot }}</div>
                        <div class="mt-1 text-xs text-navy-400 nums-fa" dir="ltr">{{ $item->sku_snapshot }}</div>
                    </div>
                    <div class="shrink-0 text-left">
                        <div class="text-xs text-navy-400 nums-fa">
                            {{ \App\Support\Digits::toPersian((string) $item->qty) }} × {{ \App\Support\Money::format($item->unit_price, false) }}
                        </div>
                        <div class="mt-1 text-sm font-bold text-navy-900 nums-fa">{{ \App\Support\Money::format($item->line_total) }}</div>
                    </div>
                </div>
            @endforeach

            <dl class="space-y-2.5 p-4 text-sm">
                @foreach([
                    ['جمع کالاها', $order->subtotal], ['تخفیف', $order->discount_total],
                    ['هزینه ارسال', $order->shipping_cost], ['مالیات', $order->tax_total],
                ] as [$label, $value])
                    @if($value)
                        <div class="flex justify-between">
                            <dt class="text-navy-500">{{ $label }}</dt>
                            <dd class="text-navy-900 nums-fa">{{ \App\Support\Money::format($value) }}</dd>
                        </div>
                    @endif
                @endforeach
                <div class="flex justify-between border-t border-navy-100 pt-3">
                    <dt class="font-bold text-navy-900">مبلغ کل</dt>
                    <dd class="text-lg font-extrabold text-navy-900 nums-fa">{{ \App\Support\Money::format($order->grand_total) }}</dd>
                </div>
            </dl>
        </div>

        <div class="card p-5">
            <h2 class="mb-4 text-sm font-bold text-navy-900">تاریخچه وضعیت</h2>
            <ol class="space-y-4 border-r-2 border-navy-100 pr-5">
                @foreach($order->statusLogs as $log)
                    <li class="relative">
                        <span class="absolute -right-[26px] top-1.5 size-3 rounded-full bg-electric-500 ring-4 ring-white"></span>
                        <div class="flex items-center gap-2">
                            <x-admin.status-badge :status="$log->to_status" />
                            <span class="text-[11px] text-navy-400 nums-fa">
                                {{ \App\Support\Digits::toPersian($log->created_at->format('Y/m/d H:i')) }} — {{ $log->actor_type }}
                            </span>
                        </div>
                        @if($log->note)<p class="mt-1.5 text-xs text-navy-500">{{ $log->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <aside class="space-y-5">
        <div class="card p-5">
            <h2 class="mb-3 text-sm font-bold text-navy-900">وضعیت فعلی</h2>
            <x-admin.status-badge :status="$order->status" />

            @if($nextList)
                <form action="{{ route('admin.orders.status', $order) }}" method="post" class="mt-5 space-y-3">
                    @csrf
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">تغییر وضعیت به</label>
                        <select name="status" class="input !py-2.5" required>
                            @foreach($nextList as $next)
                                <option value="{{ $next }}">{{ $labels[$next] ?? $next }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">کد رهگیری پستی (هنگام ارسال)</label>
                        <input type="text" name="tracking_code" value="{{ $order->tracking_code }}" dir="ltr" class="input !py-2.5">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">یادداشت</label>
                        <textarea name="note" rows="2" class="input !py-2.5"></textarea>
                    </div>
                    <button type="submit" class="btn-primary w-full !py-2.5 !text-xs">ثبت تغییر و ارسال پیامک</button>
                </form>
            @else
                <p class="mt-4 text-xs text-navy-400">این سفارش در وضعیت نهایی است.</p>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="mb-3 text-sm font-bold text-navy-900">مشتری و آدرس</h2>
            <div class="space-y-2 text-sm">
                <div class="text-navy-900">{{ $order->customer?->name ?: '—' }}</div>
                <div class="text-xs text-navy-500 nums-fa" dir="ltr">{{ $order->customer?->mobile }}</div>
                <div class="mt-3 rounded-xl bg-slate-50 p-3 text-xs leading-7 text-navy-600">
                    {{ data_get($order->address_snapshot, 'text') }}
                    <div class="mt-1.5 text-navy-400 nums-fa">
                        گیرنده: {{ data_get($order->address_snapshot, 'receiver_name') }} —
                        <span dir="ltr">{{ data_get($order->address_snapshot, 'receiver_mobile') }}</span>
                    </div>
                </div>
                <div class="pt-2 text-xs text-navy-500">روش ارسال: {{ $order->shipping_method_name ?: '—' }}</div>
                @if($order->customer_note)
                    <div class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">{{ $order->customer_note }}</div>
                @endif
            </div>
        </div>

        @if($order->transactions->isNotEmpty())
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-bold text-navy-900">تراکنش‌ها</h2>
                @foreach($order->transactions as $tx)
                    <div class="border-b border-navy-50 py-2.5 text-xs last:border-0">
                        <div class="flex justify-between">
                            <span class="text-navy-500">{{ $tx->gateway }}</span>
                            <span class="{{ $tx->status === 'success' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $tx->status }}</span>
                        </div>
                        @if($tx->ref_id)
                            <div class="mt-1 text-navy-400 nums-fa" dir="ltr">Ref: {{ $tx->ref_id }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </aside>
</div>
@endsection
