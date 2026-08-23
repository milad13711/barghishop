<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'پنل مدیریت') — {{ config('shop.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
<div class="flex min-h-screen" x-data="{ sidebar: false }">

    {{-- نوار کناری --}}
    <aside class="fixed inset-y-0 right-0 z-40 w-64 -translate-x-0 overflow-y-auto bg-navy-900 transition-transform lg:static lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center gap-2.5 px-5">
            <span class="grid size-9 place-items-center rounded-xl bg-white/10 text-gold-400">
                <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2 4.5 13.5H11l-.5 8.5L19.5 10H13l.5-8Z"/></svg>
            </span>
            <div class="leading-tight">
                <div class="text-sm font-extrabold text-white">برقی‌شاپ</div>
                <div class="text-[10px] text-navy-400">پنل مدیریت</div>
            </div>
        </div>

        <nav class="space-y-6 p-3">
            @php
                $groups = [
                    'عملیات فروش' => [
                        ['admin.dashboard', 'پیشخوان', 'M2.25 12l8.954-8.955a1.5 1.5 0 0 1 2.121 0L22.5 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
                        ['admin.orders.index', 'سفارش‌ها', 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124'],
                        ['admin.customers.index', 'مشتریان', 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                    ],
                    'کاتالوگ' => [
                        ['admin.products.index', 'محصولات', 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                        ['admin.categories.index', 'دسته‌بندی‌ها', 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75'],
                        ['admin.brands.index', 'برندها', 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z'],
                    ],
                    'محتوا و تنظیمات' => [
                        ['admin.posts.index', 'مقالات', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25'],
                        ['admin.settings.index', 'تنظیمات', 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827'],
                    ],
                ];
            @endphp

            @foreach($groups as $label => $links)
                <div>
                    <div class="mb-2 px-3 text-[10px] font-bold uppercase tracking-wider text-navy-500">{{ $label }}</div>
                    @foreach($links as [$route, $text, $icon])
                        @php $active = request()->routeIs(str_replace('.index', '', $route).'*'); @endphp
                        <a href="{{ route($route) }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                                  {{ $active ? 'bg-electric-500 text-white' : 'text-navy-200 hover:bg-white/5 hover:text-white' }}">
                            <svg class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                            </svg>
                            {{ $text }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-navy-100 bg-white/90 px-4 backdrop-blur lg:px-6">
            <button type="button" @click="sidebar = !sidebar" class="p-2 text-navy-600 lg:hidden">
                <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <h1 class="text-base font-bold text-navy-900">@yield('heading', 'پیشخوان')</h1>

            <div class="mr-auto flex items-center gap-2">
                <a href="{{ route('home') }}" target="_blank" class="btn-ghost !px-3 !py-2 !text-xs">مشاهده سایت</a>
                <form action="{{ route('admin.logout') }}" method="post">
                    @csrf
                    <button type="submit" class="rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">خروج</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-6">
            @if(session('success'))
                <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
