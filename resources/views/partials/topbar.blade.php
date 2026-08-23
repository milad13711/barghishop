<div class="hidden bg-navy-900 text-navy-100 lg:block">
    <div class="container-app flex h-10 items-center justify-between text-xs">
        <div class="flex items-center gap-6">
            <span class="flex items-center gap-1.5">
                <svg class="size-4 text-gold-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                نماینده رسمی فروش سیماران
            </span>
            <span class="text-navy-300">{{ \App\Models\Setting::get('working_hours') }}</span>
        </div>

        <div class="flex items-center gap-5">
            <a href="{{ route('pages.contact') }}" class="hover:text-white transition">تماس با ما</a>
            <a href="{{ route('wholesale') }}" class="text-gold-400 hover:text-gold-300 transition font-semibold">همکاری و خرید عمده</a>
            <a href="tel:{{ \App\Models\Setting::get('support_phone') }}" class="flex items-center gap-1.5 hover:text-white transition">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                </svg>
                <span class="nums-fa">{{ \App\Support\Digits::toPersian(\App\Models\Setting::get('support_phone') ?? '') }}</span>
            </a>
        </div>
    </div>
</div>
