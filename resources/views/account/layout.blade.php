@extends('layouts.app')

@section('content')
@php $customer = auth('customer')->user(); @endphp

<div class="container-app py-8">
    <x-breadcrumbs :items="['حساب کاربری' => route('account.dashboard')] + ($crumb ?? [])" />

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        <aside class="lg:sticky lg:top-28 lg:h-fit">
            <div class="card overflow-hidden">
                <div class="bg-navy-900 p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid size-11 place-items-center rounded-xl bg-white/10 text-gold-400 text-sm font-bold">
                            {{ mb_substr($customer->name ?: 'کاربر', 0, 1) }}
                        </span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-bold text-white">{{ $customer->name ?: 'کاربر برقی‌شاپ' }}</div>
                            <div class="mt-0.5 text-[11px] text-navy-300 nums-fa" dir="ltr">
                                {{ \App\Support\Digits::toPersian($customer->mobile) }}
                            </div>
                        </div>
                    </div>

                    @if($customer->loyaltyLevel)
                        <div class="mt-4 flex items-center justify-between rounded-xl bg-white/5 px-3 py-2">
                            <span class="text-[11px] text-navy-200">سطح باشگاه</span>
                            <span class="text-xs font-bold" style="color: {{ $customer->loyaltyLevel->color }}">
                                {{ $customer->loyaltyLevel->name }}
                            </span>
                        </div>
                    @endif
                </div>

                <nav class="p-2">
                    @foreach([
                        ['account.dashboard', 'پیشخوان'],
                        ['account.orders', 'سفارش‌های من'],
                        ['account.loyalty', 'باشگاه مشتریان'],
                    ] as [$route, $label])
                        <a href="{{ route($route) }}"
                           class="block rounded-lg px-3 py-2.5 text-sm font-medium transition
                                  {{ request()->routeIs($route.'*') ? 'bg-electric-50 text-electric-700' : 'text-navy-600 hover:bg-navy-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach

                    <form action="{{ route('auth.logout') }}" method="post">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-right text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                            خروج از حساب
                        </button>
                    </form>
                </nav>
            </div>
        </aside>

        <div>@yield('account')</div>
    </div>
</div>
@endsection
