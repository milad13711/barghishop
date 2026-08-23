@props(['resolved', 'retail' => null, 'size' => 'md'])

@php
    $sizes = [
        'sm' => ['amount' => 'text-base', 'compare' => 'text-xs'],
        'md' => ['amount' => 'text-lg', 'compare' => 'text-sm'],
        'lg' => ['amount' => 'text-3xl', 'compare' => 'text-base'],
    ][$size];
@endphp

@if($resolved->hidden)
    <a href="{{ route('auth.login') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-electric-600 hover:underline">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
        </svg>
        {{ __('shop.login_to_see') }}
    </a>
@elseif($resolved->amount <= 0)
    <span class="text-sm font-semibold text-navy-400">{{ __('shop.call_for_price') }}</span>
@else
    <div class="flex flex-col gap-0.5">
        @if($resolved->hasDiscount())
            <div class="flex items-center gap-2">
                <span class="badge bg-rose-50 text-rose-600 nums-fa">
                    {{ \App\Support\Digits::toPersian((string) $resolved->discountPercent()) }}٪ تخفیف
                </span>
                <span class="{{ $sizes['compare'] }} text-navy-300 line-through nums-fa">
                    {{ \App\Support\Money::format($resolved->compareAt, false) }}
                </span>
            </div>
        @endif

        <div class="flex items-baseline gap-1.5">
            <span class="{{ $sizes['amount'] }} font-extrabold text-navy-900 nums-fa">
                {{ \App\Support\Money::format($resolved->amount, false) }}
            </span>
            <span class="text-xs font-medium text-navy-400">تومان</span>
        </div>

        @if($resolved->tier->is_wholesale)
            <div class="flex items-center gap-1.5 text-xs">
                <span class="badge bg-gold-50 text-gold-700">{{ $resolved->tier->name }}</span>
                @if($retail && $retail->amount > $resolved->amount)
                    <span class="text-navy-400 nums-fa">
                        {{ __('shop.retail_price') }}: {{ \App\Support\Money::format($retail->amount, false) }}
                    </span>
                @endif
            </div>
        @endif
    </div>
@endif
