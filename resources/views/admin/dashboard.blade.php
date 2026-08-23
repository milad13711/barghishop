@extends('admin.layouts.app')
@section('heading', 'پیشخوان')

@section('content')
<div class="space-y-6">

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['سفارش‌های امروز', \App\Support\Digits::toPersian((string) $stats['today_orders']), 'عدد'],
            ['فروش امروز', \App\Support\Money::format($stats['today_revenue'], false), 'تومان'],
            ['فروش ۳۰ روز اخیر', \App\Support\Money::format($stats['month_revenue'], false), 'تومان'],
            ['مشتریان', \App\Support\Digits::toPersian((string) $stats['customers']), 'نفر'],
        ] as [$label, $value, $unit])
            <div class="card p-5">
                <div class="text-xs text-navy-400">{{ $label }}</div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-navy-900 nums-fa">{{ $value }}</span>
                    <span class="text-xs text-navy-400">{{ $unit }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- کارهایی که نیاز به رسیدگی دارند --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['در انتظار پرداخت', $stats['pending'], route('admin.orders.index', ['status' => 'pending_payment']), 'bg-amber-100 text-amber-800'],
            ['آماده‌سازی سفارش', $stats['processing'], route('admin.orders.index', ['status' => 'paid']), 'bg-electric-100 text-electric-800'],
            ['درخواست همکاری', $stats['wholesale_requests'], route('admin.customers.index', ['status' => 'pending']), 'bg-gold-100 text-gold-800'],
            ['موجودی رو به اتمام', $stats['low_stock'], route('admin.products.index'), 'bg-rose-100 text-rose-800'],
        ] as [$label, $count, $url, $badge])
            <a href="{{ $url }}" class="card card-hover flex items-center justify-between p-5">
                <span class="text-sm font-medium text-navy-700">{{ $label }}</span>
                <span class="badge {{ $badge }} nums-fa text-sm">
                    {{ \App\Support\Digits::toPersian((string) $count) }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-navy-900">آخرین سفارش‌ها</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-electric-600">همه ←</a>
            </div>

            @if($recentOrders->isEmpty())
                <p class="py-8 text-center text-sm text-navy-400">هنوز سفارشی ثبت نشده است.</p>
            @else
                <div class="divide-y divide-navy-50">
                    @foreach($recentOrders as $order)
                        <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between py-3 transition hover:bg-slate-50">
                            <div>
                                <div class="text-sm font-medium text-navy-900 nums-fa" dir="ltr">{{ $order->code }}</div>
                                <div class="mt-0.5 text-xs text-navy-400">{{ $order->customer?->name ?: $order->customer?->mobile ?: 'مهمان' }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-navy-900 nums-fa">{{ \App\Support\Money::format($order->grand_total, false) }}</span>
                                <x-admin.status-badge :status="$order->status" />
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="mb-4 text-sm font-bold text-navy-900">موجودی رو به اتمام</h2>

            @if($lowStock->isEmpty())
                <p class="py-8 text-center text-sm text-navy-400">همه محصولات موجودی کافی دارند.</p>
            @else
                <div class="divide-y divide-navy-50">
                    @foreach($lowStock as $product)
                        <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center justify-between py-3">
                            <span class="line-clamp-1 text-sm text-navy-700">{{ $product->name }}</span>
                            <span class="badge {{ $product->stock == 0 ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }} nums-fa">
                                {{ \App\Support\Digits::toPersian((string) $product->stock) }} عدد
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
