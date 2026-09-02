@php
    $footerCategories = \App\Models\Category::active()->roots()->orderBy('sort')->limit(6)->get();
    $footerPosts = \App\Models\Post::published()->latest('published_at')->limit(4)->get();
@endphp

<footer class="mt-16 bg-navy-900 text-navy-200">
    {{-- نوار اعتماد --}}
    <div class="border-b border-white/10">
        <div class="container-app grid grid-cols-2 gap-6 py-8 lg:grid-cols-4">
            @foreach([
                ['گارانتی اصالت کالا', 'تمام محصولات اورجینال و دارای گارانتی رسمی'],
                ['ارسال به سراسر ایران', 'پست پیشتاز، تیپاکس و پیک تهران'],
                ['پشتیبانی تخصصی', 'مشاوره رایگان پیش و پس از خرید'],
                ['پرداخت امن', 'درگاه بانکی معتبر با نماد اعتماد'],
            ] as [$title, $subtitle])
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 grid size-10 shrink-0 place-items-center rounded-xl bg-white/5 text-gold-400">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-white">{{ $title }}</div>
                        <div class="mt-0.5 text-xs text-navy-300 leading-6">{{ $subtitle }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="container-app grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="flex items-center gap-2.5">
                <x-logo variant="light" class="h-10" />
                <span class="text-lg font-extrabold text-white">برقی‌شاپ</span>
            </div>
            <p class="mt-4 text-sm leading-7 text-navy-300">
                برقی‌شاپ نماینده فروش محصولات سیماران است؛ ارائه‌دهنده آیفون تصویری، پنل، درب‌بازکن
                و تجهیزات برق ساختمان با قیمت نمایندگی برای مصرف‌کننده و همکاران.
            </p>
            <div class="mt-5 flex gap-2">
                @foreach(['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'whatsapp' => 'واتساپ'] as $key => $label)
                    @if($url = \App\Models\Setting::get($key))
                        <a href="{{ $url }}" target="_blank" rel="noopener nofollow"
                           class="grid size-9 place-items-center rounded-lg bg-white/5 text-navy-200 transition hover:bg-white/10 hover:text-white"
                           aria-label="{{ $label }}">
                            <span class="text-xs">{{ mb_substr($label, 0, 1) }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-sm font-bold text-white">دسته‌بندی محصولات</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach($footerCategories as $category)
                    <li><a href="{{ route('shop.category', $category) }}" class="text-navy-300 transition hover:text-white">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold text-white">آخرین مقالات</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach($footerPosts as $post)
                    <li><a href="{{ route('blog.show', $post) }}" class="text-navy-300 transition hover:text-white line-clamp-1">{{ $post->title }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold text-white">تماس با ما</h3>
            <ul class="mt-4 space-y-3 text-sm text-navy-300">
                <li class="nums-fa">تلفن: {{ \App\Support\Digits::toPersian(\App\Models\Setting::get('support_phone') ?? '') }}</li>
                <li class="leading-7">{{ \App\Models\Setting::get('address') }}</li>
                <li>{{ \App\Models\Setting::get('working_hours') }}</li>
            </ul>

            <div class="mt-5 flex gap-3">
                <div class="grid h-20 w-20 place-items-center rounded-xl bg-white/5 text-[10px] text-navy-400 text-center leading-4">نماد<br>اعتماد</div>
                <div class="grid h-20 w-20 place-items-center rounded-xl bg-white/5 text-[10px] text-navy-400 text-center leading-4">ساماندهی</div>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-app flex flex-col items-center justify-between gap-2 py-5 text-xs text-navy-400 sm:flex-row">
            <span>© {{ \App\Support\Digits::toPersian((string) \App\Support\Jalali::currentYear()) }} برقی‌شاپ — تمام حقوق محفوظ است.</span>
            <span>قیمت‌ها به‌روزرسانی روزانه می‌شوند.</span>
        </div>
    </div>
</footer>
