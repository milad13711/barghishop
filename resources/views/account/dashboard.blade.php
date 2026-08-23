@extends('account.layout')

@section('account')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach([
            ['کل سفارش‌ها', $stats['orders_count'], 'electric'],
            ['در جریان', $stats['in_progress'], 'gold'],
            ['تحویل‌شده', $stats['delivered'], 'emerald'],
        ] as [$label, $value, $color])
            <div class="card p-5">
                <div class="text-xs text-navy-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-extrabold text-navy-900 nums-fa">
                    {{ \App\Support\Digits::toPersian((string) $value) }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- باشگاه مشتریان --}}
    <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-bold text-navy-900">امتیاز باشگاه مشتریان</h2>
                <p class="mt-1 text-2xl font-extrabold text-gold-600 nums-fa">
                    {{ \App\Support\Digits::toPersian((string) $customer->points) }}
                    <span class="text-sm font-medium text-navy-400">امتیاز</span>
                </p>
            </div>
            <a href="{{ route('account.loyalty') }}" class="btn-ghost !py-2.5 !text-xs">مشاهده جزئیات</a>
        </div>

        @if($nextLevel)
            @php
                $current = $customer->loyaltyLevel?->min_points ?? 0;
                $span = max(1, $nextLevel->min_points - $current);
                $progress = min(100, (int) round(($customer->points - $current) / $span * 100));
            @endphp
            <div class="mt-5">
                <div class="mb-2 flex justify-between text-[11px] text-navy-500 nums-fa">
                    <span>تا سطح «{{ $nextLevel->name }}»</span>
                    <span>{{ \App\Support\Digits::toPersian((string) max(0, $nextLevel->min_points - $customer->points)) }} امتیاز مانده</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-navy-100">
                    <div class="h-full rounded-full bg-gold-500" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- درخواست همکاری --}}
    @if($customer->wholesale_status === \App\Models\Customer::WHOLESALE_NONE)
        <div class="card flex flex-wrap items-center justify-between gap-4 bg-navy-900 p-6">
            <div>
                <h2 class="text-sm font-bold text-white">همکار یا پیمانکار هستید؟</h2>
                <p class="mt-1.5 text-xs leading-6 text-navy-300">با ثبت درخواست، قیمت‌های عمده برای حساب شما فعال می‌شود.</p>
            </div>
            <a href="{{ route('wholesale') }}" class="btn-gold !py-2.5 !text-xs">ثبت درخواست همکاری</a>
        </div>
    @elseif($customer->wholesale_status === \App\Models\Customer::WHOLESALE_PENDING)
        <div class="card bg-gold-50 p-5 text-sm text-gold-800">
            درخواست همکاری شما در حال بررسی است. نتیجه از طریق پیامک اطلاع داده می‌شود.
        </div>
    @elseif($customer->isWholesaler())
        <div class="card bg-emerald-50 p-5 text-sm text-emerald-800">
            حساب شما به‌عنوان <strong>{{ $customer->effectiveTier()->name }}</strong> تأیید شده است؛
            قیمت‌های عمده در سراسر فروشگاه برای شما نمایش داده می‌شود.
        </div>
    @endif

    {{-- آخرین سفارش‌ها --}}
    <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-bold text-navy-900">آخرین سفارش‌ها</h2>
            <a href="{{ route('account.orders') }}" class="text-xs font-semibold text-electric-600">همه سفارش‌ها ←</a>
        </div>

        @include('account.partials.order-table', ['orders' => $orders])
    </div>
</div>
@endsection
