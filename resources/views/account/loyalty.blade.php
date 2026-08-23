@extends('account.layout')

@section('account')
<div class="space-y-6">
    <div class="card p-6">
        <h1 class="text-sm font-bold text-navy-900">باشگاه مشتریان برقی‌شاپ</h1>
        <p class="mt-3 text-3xl font-extrabold text-gold-600 nums-fa">
            {{ \App\Support\Digits::toPersian((string) $customer->points) }}
            <span class="text-sm font-medium text-navy-400">امتیاز</span>
        </p>
        <p class="mt-3 text-xs leading-7 text-navy-500">
            با هر خرید امتیاز می‌گیرید و با بالا رفتن سطح، تخفیف دائمی و مزایای بیشتری دریافت می‌کنید.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($levels as $level)
            @php $isCurrent = $customer->loyalty_level_id === $level->id; @endphp
            <div class="card p-5 {{ $isCurrent ? 'ring-2 ring-gold-500' : '' }}">
                <div class="flex items-center gap-2">
                    <span class="size-3 rounded-full" style="background: {{ $level->color }}"></span>
                    <h2 class="text-sm font-bold text-navy-900">{{ $level->name }}</h2>
                    @if($isCurrent)<span class="badge bg-gold-100 text-gold-800">سطح شما</span>@endif
                </div>
                <p class="mt-3 text-xs text-navy-400 nums-fa">
                    از {{ \App\Support\Digits::toPersian((string) $level->min_points) }} امتیاز
                </p>
                @if($level->discount_percent > 0)
                    <p class="mt-1 text-xs font-semibold text-emerald-600 nums-fa">
                        {{ \App\Support\Digits::toPersian((string) (int) $level->discount_percent) }}٪ تخفیف دائمی
                    </p>
                @endif
                @if($level->benefits)
                    <ul class="mt-3 space-y-1.5">
                        @foreach($level->benefits as $benefit)
                            <li class="text-[11px] leading-6 text-navy-500">• {{ $benefit }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <div class="card p-6">
        <h2 class="mb-4 text-sm font-bold text-navy-900">تاریخچه امتیاز</h2>
        @if($entries->isEmpty())
            <p class="py-8 text-center text-sm text-navy-400">هنوز امتیازی ثبت نشده است.</p>
        @else
            <div class="divide-y divide-navy-50">
                @foreach($entries as $entry)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <div class="text-sm text-navy-800">{{ $entry->reason }}</div>
                            <div class="mt-0.5 text-[11px] text-navy-400 nums-fa">
                                {{ \App\Support\Digits::toPersian($entry->created_at->format('Y/m/d')) }}
                            </div>
                        </div>
                        <span class="text-sm font-bold nums-fa {{ $entry->points > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $entry->points > 0 ? '+' : '' }}{{ \App\Support\Digits::toPersian((string) $entry->points) }}
                        </span>
                    </div>
                @endforeach
            </div>
            @if($entries->hasPages())<div class="mt-6">{{ $entries->links() }}</div>@endif
        @endif
    </div>
</div>
@endsection
