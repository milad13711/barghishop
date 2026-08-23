@extends('account.layout')

@section('account')
<div class="space-y-6">
    <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-bold text-navy-900">سفارش <span dir="ltr" class="nums-fa">{{ $order->code }}</span></h1>
                <p class="mt-1 text-xs text-navy-400 nums-fa">
                    ثبت‌شده در {{ \App\Support\Digits::toPersian($order->created_at->format('Y/m/d H:i')) }}
                </p>
            </div>
            @if($order->tracking_code)
                <div class="rounded-xl bg-electric-50 px-4 py-2.5 text-xs">
                    <span class="text-navy-500">کد رهگیری پستی:</span>
                    <span class="font-bold text-electric-700 nums-fa" dir="ltr">{{ $order->tracking_code }}</span>
                </div>
            @endif
        </div>

        {{-- خط زمانی وضعیت --}}
        @if($order->statusLogs->isNotEmpty())
            <ol class="mt-6 space-y-4 border-r-2 border-navy-100 pr-5">
                @foreach($order->statusLogs as $log)
                    <li class="relative">
                        <span class="absolute -right-[26px] top-1.5 size-3 rounded-full bg-electric-500 ring-4 ring-white"></span>
                        <div class="text-sm font-medium text-navy-900">{{ $log->to_status }}</div>
                        <div class="mt-0.5 text-[11px] text-navy-400 nums-fa">
                            {{ \App\Support\Digits::toPersian($log->created_at->format('Y/m/d H:i')) }}
                        </div>
                        @if($log->note)<p class="mt-1 text-xs text-navy-500">{{ $log->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="card divide-y divide-navy-50">
        @foreach($order->items as $item)
            <div class="flex items-center gap-4 p-5">
                <div class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-slate-50">
                    @if($item->product?->primary_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->product->primary_image) }}"
                             alt="" class="size-full object-contain p-1.5">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="line-clamp-2 text-sm font-medium text-navy-900">{{ $item->name_snapshot }}</div>
                    <div class="mt-1 text-xs text-navy-400 nums-fa">
                        {{ \App\Support\Digits::toPersian((string) $item->qty) }} عدد ×
                        {{ \App\Support\Money::format($item->unit_price) }}
                    </div>
                </div>
                <div class="text-sm font-bold text-navy-900 nums-fa">{{ \App\Support\Money::format($item->line_total) }}</div>
            </div>
        @endforeach
    </div>

    <div class="card p-6">
        <dl class="space-y-3 text-sm">
            @foreach([
                ['جمع کالاها', $order->subtotal],
                ['تخفیف', -$order->discount_total],
                ['هزینه ارسال', $order->shipping_cost],
            ] as [$label, $value])
                @if($value)
                    <div class="flex justify-between">
                        <dt class="text-navy-500">{{ $label }}</dt>
                        <dd class="font-medium text-navy-900 nums-fa">{{ \App\Support\Money::format(abs($value)) }}</dd>
                    </div>
                @endif
            @endforeach
            <div class="flex justify-between border-t border-navy-100 pt-3">
                <dt class="font-bold text-navy-900">مبلغ کل</dt>
                <dd class="text-lg font-extrabold text-navy-900 nums-fa">{{ \App\Support\Money::format($order->grand_total) }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
