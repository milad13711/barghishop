@extends('layouts.app')

@push('schema')
    {!! \App\Support\Seo\Schema::render($schema) !!}
@endpush

@section('content')

{{-- هیرو --}}
<section class="relative overflow-hidden bg-navy-900">
    <div class="absolute inset-0 opacity-[0.07]"
         style="background-image:radial-gradient(circle at 1px 1px, white 1px, transparent 0);background-size:28px 28px"></div>
    <div class="absolute -left-32 -top-32 size-96 rounded-full bg-electric-500/20 blur-3xl"></div>
    <div class="absolute -bottom-40 right-0 size-96 rounded-full bg-gold-500/10 blur-3xl"></div>

    <div class="container-app relative grid items-center gap-10 py-14 lg:grid-cols-2 lg:py-20">
        <div>
            <span class="badge bg-white/10 text-gold-400 backdrop-blur">نماینده رسمی فروش سیماران</span>

            <h1 class="mt-5 text-3xl font-extrabold leading-[1.35] text-white lg:text-[2.75rem] lg:leading-[1.3]">
                آیفون تصویری و تجهیزات برق ساختمان،
                <span class="text-gold-400">با قیمت نمایندگی</span>
            </h1>

            <p class="mt-5 max-w-lg text-sm leading-8 text-navy-200 lg:text-base">
                از انتخاب مدل مناسب تا نصب و پشتیبانی، کنار شما هستیم.
                خرید تکی برای مصرف‌کننده و قیمت پلکانی برای همکاران و پروژه‌های ساختمانی.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('shop.index') }}" class="btn-gold">
                    مشاهده محصولات
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <a href="{{ route('wholesale') }}" class="btn bg-white/10 text-white backdrop-blur hover:bg-white/20">
                    درخواست همکاری و خرید عمده
                </a>
            </div>

            <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-white/10 pt-6">
                @foreach([['۱۰۰٪', 'اصالت کالا'], ['۲۴ ماه', 'گارانتی'], ['۳۱ استان', 'پوشش ارسال']] as [$value, $label])
                    <div>
                        <dt class="text-xl font-extrabold text-white nums-fa">{{ $value }}</dt>
                        <dd class="mt-1 text-xs text-navy-300">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="relative hidden lg:block">
            <div class="relative mx-auto aspect-[4/5] w-full max-w-sm rounded-[2rem] bg-gradient-to-br from-white/10 to-white/5 p-6 ring-1 ring-white/10 backdrop-blur">
                <div class="flex h-full flex-col rounded-3xl bg-navy-950/60 p-5 ring-1 ring-white/10">
                    <div class="mx-auto h-1.5 w-16 rounded-full bg-white/20"></div>
                    <div class="mt-5 flex-1 rounded-2xl bg-gradient-to-b from-electric-500/25 to-navy-900/60 ring-1 ring-white/10"></div>
                    <div class="mt-5 grid grid-cols-4 gap-2.5">
                        @foreach(range(1, 4) as $i)
                            <div class="h-9 rounded-xl bg-white/10 ring-1 ring-white/10"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- نوار اعتماد --}}
<section class="border-b border-navy-100 bg-white">
    <div class="container-app grid grid-cols-2 gap-5 py-6 lg:grid-cols-4">
        @foreach([
            ['ارسال سریع', 'به سراسر ایران'],
            ['ضمانت اصالت', 'کالای اورجینال'],
            ['پرداخت امن', 'درگاه بانکی معتبر'],
            ['مشاوره رایگان', 'پیش و پس از خرید'],
        ] as [$title, $sub])
            <div class="flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-electric-50 text-electric-600">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </span>
                <div>
                    <div class="text-sm font-bold text-navy-900">{{ $title }}</div>
                    <div class="text-xs text-navy-400">{{ $sub }}</div>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- دسته‌بندی‌ها --}}
<section class="container-app py-12">
    <x-section-heading title="خرید بر اساس دسته‌بندی"
                       subtitle="دسته مورد نظر خود را انتخاب کنید"
                       :href="route('shop.index')" />

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($categories as $category)
            <a href="{{ route('shop.category', $category) }}" class="card card-hover group flex items-center gap-4 p-5">
                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-navy-50 text-navy-700 transition group-hover:bg-electric-500 group-hover:text-white">
                    <svg class="size-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-bold text-navy-900">{{ $category->name }}</div>
                    <div class="mt-1 text-xs text-navy-400 nums-fa">
                        {{ \App\Support\Digits::toPersian((string) $category->products()->published()->count()) }} کالا
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</section>

{{-- محصولات ویژه --}}
@if($featured->isNotEmpty())
<section class="container-app py-6">
    <x-section-heading title="پیشنهاد ویژه برقی‌شاپ"
                       subtitle="منتخب پرفروش‌ترین و باکیفیت‌ترین محصولات"
                       :href="route('shop.index')" />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach($featured as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

{{-- بنر خرید عمده --}}
<section class="container-app py-12">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-navy-900 to-navy-700 px-8 py-10 lg:px-12 lg:py-14">
        <div class="absolute -left-20 -top-20 size-64 rounded-full bg-gold-500/15 blur-3xl"></div>

        <div class="relative flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
            <div class="max-w-xl">
                <span class="badge bg-gold-500 text-navy-950">ویژه همکاران</span>
                <h2 class="mt-4 text-2xl font-extrabold text-white lg:text-3xl">قیمت عمده، پلکانی و بدون واسطه</h2>
                <p class="mt-3 text-sm leading-8 text-navy-200">
                    اگر برقکار، پیمانکار یا فروشنده تجهیزات ساختمانی هستید، با ثبت درخواست همکاری
                    قیمت‌های ویژه بر اساس تعداد سفارش برای شما فعال می‌شود.
                </p>
            </div>
            <a href="{{ route('wholesale') }}" class="btn-gold shrink-0">ثبت درخواست همکاری</a>
        </div>
    </div>
</section>

{{-- جدیدترین‌ها --}}
@if($newest->isNotEmpty())
<section class="container-app py-6">
    <x-section-heading title="جدیدترین محصولات" :href="route('shop.index', ['sort' => 'newest'])" />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach($newest as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>
</section>
@endif

{{-- مقالات --}}
@if($posts->isNotEmpty())
<section class="container-app py-12">
    <x-section-heading title="راهنمای خرید و آموزش"
                       subtitle="پیش از خرید بخوانید تا انتخاب درستی داشته باشید"
                       :href="route('blog.index')" />

    <div class="grid gap-5 md:grid-cols-3">
        @foreach($posts as $post)
            <article class="card card-hover overflow-hidden">
                <a href="{{ route('blog.show', $post) }}" class="block aspect-[16/9] bg-gradient-to-br from-navy-100 to-navy-50"></a>
                <div class="p-5">
                    @if($post->category)
                        <span class="badge bg-electric-50 text-electric-700">{{ $post->category->name }}</span>
                    @endif
                    <h3 class="mt-3 line-clamp-2 text-sm font-bold leading-7 text-navy-900">
                        <a href="{{ route('blog.show', $post) }}" class="transition hover:text-electric-600">{{ $post->title }}</a>
                    </h3>
                    <p class="mt-2 line-clamp-2 text-xs leading-6 text-navy-500">{{ $post->summary(120) }}</p>
                    <div class="mt-4 flex items-center gap-1.5 text-[11px] text-navy-400 nums-fa">
                        <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        {{ \App\Support\Digits::toPersian((string) $post->reading_minutes) }} دقیقه مطالعه
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

@endsection
