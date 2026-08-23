@extends('layouts.app')

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="['خرید عمده و همکاری' => null]" />

    <div class="card overflow-hidden">
        <div class="relative bg-gradient-to-l from-navy-900 to-navy-700 px-8 py-12 lg:px-12">
            <div class="absolute -left-20 -top-20 size-64 rounded-full bg-gold-500/15 blur-3xl"></div>
            <div class="relative max-w-2xl">
                <span class="badge bg-gold-500 text-navy-950">ویژه همکاران صنفی</span>
                <h1 class="mt-4 text-2xl font-extrabold text-white lg:text-3xl">خرید عمده تجهیزات سیماران</h1>
                <p class="mt-4 text-sm leading-8 text-navy-200">
                    اگر برقکار، پیمانکار ساختمان یا فروشنده تجهیزات هستید، با ثبت درخواست همکاری
                    قیمت‌های ویژه بر اساس تعداد سفارش برای حساب شما فعال می‌شود.
                </p>
            </div>
        </div>

        <div class="p-8 lg:p-12">
            <h2 class="mb-6 text-lg font-extrabold text-navy-900">سطوح همکاری</h2>

            <div class="grid gap-5 md:grid-cols-3">
                @foreach($tiers as $tier)
                    <div class="rounded-2xl bg-slate-50 p-6 ring-1 ring-navy-100">
                        <h3 class="text-base font-bold text-navy-900">{{ $tier->name }}</h3>
                        <p class="mt-3 text-3xl font-extrabold text-gold-600 nums-fa">
                            تا {{ \App\Support\Digits::toPersian((string) (int) $tier->fallback_discount_percent) }}٪
                            <span class="text-sm font-medium text-navy-400">تخفیف</span>
                        </p>
                        <p class="mt-3 text-xs leading-7 text-navy-500">
                            قیمت‌گذاری پلکانی؛ هرچه تعداد سفارش بیشتر باشد، قیمت واحد پایین‌تر می‌آید.
                        </p>
                        @if($tier->requires_approval)
                            <p class="mt-4 text-[11px] text-navy-400">نیازمند تأیید مدارک صنفی</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-10 rounded-2xl bg-electric-50 p-6">
                <h2 class="text-base font-bold text-navy-900">مراحل ثبت‌نام</h2>
                <ol class="mt-4 grid gap-4 sm:grid-cols-4">
                    @foreach([
                        'ثبت‌نام با شماره موبایل',
                        'تکمیل اطلاعات صنفی',
                        'بررسی و تأیید توسط کارشناس',
                        'فعال شدن قیمت همکار',
                    ] as $i => $step)
                        <li class="flex gap-3">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-electric-500 text-xs font-bold text-white nums-fa">
                                {{ \App\Support\Digits::toPersian((string) ($i + 1)) }}
                            </span>
                            <span class="text-xs leading-6 text-navy-700">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-6">
                    @auth('customer')
                        <a href="{{ route('account.wholesale') }}" class="btn-primary">تکمیل درخواست همکاری</a>
                    @else
                        <a href="{{ route('auth.login') }}" class="btn-primary">ورود و ثبت درخواست همکاری</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
