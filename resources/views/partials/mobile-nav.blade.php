@php $cartCount = app(\App\Services\Cart\CartService::class)->count(); @endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-navy-100 bg-white/95 backdrop-blur-lg lg:hidden">
    <div class="grid grid-cols-4">
        @php
            $links = [
                ['home', 'خانه', 'M2.25 12l8.954-8.955a1.5 1.5 0 0 1 2.121 0L22.5 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
                ['shop.index', 'محصولات', 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5'],
                ['cart.index', 'سبد خرید', 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272'],
                [auth('customer')->check() ? 'account.dashboard' : 'auth.login', 'حساب من', 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
            ];
        @endphp

        @foreach($links as [$route, $label, $path])
            @php $active = request()->routeIs($route); @endphp
            <a href="{{ route($route) }}"
               class="relative flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium transition
                      {{ $active ? 'text-electric-600' : 'text-navy-400' }}">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                </svg>
                {{ $label }}
                @if($route === 'cart.index' && $cartCount)
                    <span class="absolute right-[22%] top-1.5 grid size-4 place-items-center rounded-full bg-gold-500 text-[10px] font-bold text-navy-950 nums-fa">
                        {{ \App\Support\Digits::toPersian((string) $cartCount) }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</nav>

<div class="h-16 lg:hidden"></div>
