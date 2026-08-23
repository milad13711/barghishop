@php
    $menuCategories = \App\Models\Category::active()->roots()->where('show_in_menu', true)
        ->with(['children' => fn ($q) => $q->active()])->orderBy('sort')->get();
    $cartCount = app(\App\Services\Cart\CartService::class)->count();
@endphp

<header class="sticky top-0 z-40 border-b border-navy-100 bg-white/90 backdrop-blur-lg">
    <div class="container-app">
        <div class="flex h-16 items-center gap-4 lg:h-20 lg:gap-8">

            {{-- منوی موبایل --}}
            <button type="button" class="lg:hidden -mr-2 p-2 text-navy-700" x-data @click="$dispatch('open-menu')" aria-label="منو">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="grid size-10 place-items-center rounded-xl bg-navy-900 text-gold-400 lg:size-11">
                    <svg class="size-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 2 4.5 13.5H11l-.5 8.5L19.5 10H13l.5-8Z"/>
                    </svg>
                </span>
                <span class="hidden sm:block leading-tight">
                    <span class="block text-lg font-extrabold text-navy-900">برقی‌شاپ</span>
                    <span class="block text-[11px] text-navy-400">نماینده فروش سیماران</span>
                </span>
            </a>

            {{-- جستجو --}}
            <form action="{{ route('search') }}" method="get" class="relative hidden flex-1 md:block">
                <input type="search" name="q" value="{{ request('q') }}"
                       class="input pr-11 bg-slate-50 ring-navy-100 focus:bg-white"
                       placeholder="{{ __('shop.search_placeholder') }}" aria-label="{{ __('shop.search') }}">
                <svg class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-navy-300"
                     fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </form>

            <div class="mr-auto flex items-center gap-1.5 lg:gap-3">
                @auth('customer')
                    <a href="{{ route('account.dashboard') }}" class="btn-ghost !px-3 !py-2.5 lg:!px-4">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        <span class="hidden lg:inline">{{ auth('customer')->user()->name ?: 'حساب من' }}</span>
                    </a>
                @else
                    <a href="{{ route('auth.login') }}" class="btn-ghost !px-3 !py-2.5 lg:!px-4">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        <span class="hidden lg:inline">{{ __('shop.login') }}</span>
                    </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="btn-navy relative !px-3 !py-2.5 lg:!px-4">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                    </svg>
                    <span class="hidden lg:inline">{{ __('shop.cart') }}</span>
                    @if($cartCount)
                        <span class="absolute -top-1.5 -left-1.5 grid size-5 place-items-center rounded-full bg-gold-500 text-[11px] font-bold text-navy-950 nums-fa">
                            {{ \App\Support\Digits::toPersian((string) $cartCount) }}
                        </span>
                    @endif
                </a>
            </div>
        </div>

        {{-- ناوبری دسته‌ها --}}
        <nav class="hidden h-12 items-center gap-1 lg:flex">
            <a href="{{ route('shop.index') }}"
               class="flex items-center gap-2 rounded-xl bg-navy-900 px-4 py-2 text-sm font-bold text-white">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                همه محصولات
            </a>

            @foreach($menuCategories as $category)
                <div class="group relative">
                    <a href="{{ route('shop.category', $category) }}"
                       class="flex items-center gap-1 rounded-xl px-4 py-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50 hover:text-navy-900">
                        {{ $category->name }}
                        @if($category->children->isNotEmpty())
                            <svg class="size-3.5 text-navy-300 transition group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        @endif
                    </a>

                    @if($category->children->isNotEmpty())
                        <div class="invisible absolute right-0 top-full z-50 w-60 translate-y-1 opacity-0 transition
                                    group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                            <div class="card mt-1 overflow-hidden p-2">
                                @foreach($category->children as $child)
                                    <a href="{{ route('shop.category', $child) }}"
                                       class="block rounded-lg px-3 py-2 text-sm text-navy-600 transition hover:bg-electric-50 hover:text-electric-700">
                                        {{ $child->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <a href="{{ route('blog.index') }}" class="rounded-xl px-4 py-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50">
                {{ __('shop.blog') }}
            </a>
        </nav>
    </div>

    {{-- جستجوی موبایل --}}
    <div class="border-t border-navy-50 px-4 py-2.5 md:hidden">
        <form action="{{ route('search') }}" method="get" class="relative">
            <input type="search" name="q" value="{{ request('q') }}"
                   class="input pr-11 bg-slate-50 !py-2.5 ring-navy-100"
                   placeholder="{{ __('shop.search_placeholder') }}">
            <svg class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-navy-300"
                 fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
        </form>
    </div>
</header>
