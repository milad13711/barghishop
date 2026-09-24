@php
    $drawerCategories = \App\Models\Category::active()->roots()->where('show_in_menu', true)
        ->with(['children' => fn ($q) => $q->active()])->orderBy('sort')->get();
@endphp

{{-- کشوی منوی موبایل — دکمه همبرگری هدر رویداد open-menu را می‌فرستد --}}
<div x-data="{ open: false, expanded: null }"
     x-on:open-menu.window="open = true"
     x-on:keydown.escape.window="open = false"
     x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
     x-cloak
     class="lg:hidden">

    <div x-show="open" x-transition.opacity @click="open = false"
         class="fixed inset-0 z-[60] bg-navy-950/60 backdrop-blur-sm" aria-hidden="true"></div>

    <aside x-show="open" x-transition:enter="transition duration-200 ease-out"
           x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-150 ease-in"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="fixed inset-y-0 right-0 z-[70] flex w-[85%] max-w-sm flex-col bg-white shadow-2xl"
           role="dialog" aria-modal="true" aria-label="منوی اصلی">

        <div class="flex h-16 shrink-0 items-center justify-between border-b border-navy-100 px-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <x-logo class="h-9" />
                <span class="text-base font-extrabold text-navy-900">برقی‌شاپ</span>
            </a>
            <button type="button" @click="open = false" class="p-2 text-navy-500" aria-label="بستن منو">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-3 text-sm">
            <a href="{{ route('shop.index') }}" class="flex items-center rounded-xl bg-navy-900 px-4 py-3 font-bold text-white">
                همه محصولات
            </a>

            <div class="mt-3 space-y-1">
                @foreach($drawerCategories as $category)
                    <div>
                        <div class="flex items-center">
                            <a href="{{ route('shop.category', $category) }}"
                               class="flex-1 rounded-xl px-4 py-3 font-medium text-navy-800 hover:bg-navy-50">{{ $category->name }}</a>
                            @if($category->children->isNotEmpty())
                                <button type="button" class="p-3 text-navy-400"
                                        @click="expanded = expanded === {{ $category->id }} ? null : {{ $category->id }}"
                                        :aria-expanded="expanded === {{ $category->id }}" aria-label="زیردسته‌ها">
                                    <svg class="size-4 transition" :class="expanded === {{ $category->id }} && 'rotate-180'"
                                         fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        @if($category->children->isNotEmpty())
                            <div x-show="expanded === {{ $category->id }}" x-cloak class="mr-4 border-r border-navy-100 pr-2">
                                @foreach($category->children as $child)
                                    <a href="{{ route('shop.category', $child) }}"
                                       class="block rounded-lg px-3 py-2.5 text-navy-600 hover:bg-navy-50">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4 space-y-1 border-t border-navy-100 pt-4">
                <a href="{{ route('blog.index') }}" class="block rounded-xl px-4 py-3 text-navy-700 hover:bg-navy-50">مقالات</a>
                <a href="{{ route('wholesale') }}" class="block rounded-xl px-4 py-3 font-semibold text-gold-700 hover:bg-gold-50">همکاری و خرید عمده</a>
                <a href="{{ route('pages.about') }}" class="block rounded-xl px-4 py-3 text-navy-700 hover:bg-navy-50">درباره ما</a>
                <a href="{{ route('pages.contact') }}" class="block rounded-xl px-4 py-3 text-navy-700 hover:bg-navy-50">تماس با ما</a>
            </div>
        </nav>

        <div class="shrink-0 border-t border-navy-100 p-3">
            @auth('customer')
                <a href="{{ route('account.dashboard') }}" class="btn-navy w-full">حساب کاربری من</a>
            @else
                <a href="{{ route('auth.login') }}" class="btn-primary w-full">ورود / ثبت‌نام</a>
            @endauth
        </div>
    </aside>
</div>
