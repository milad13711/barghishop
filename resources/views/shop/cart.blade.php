@extends('layouts.app')

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="['سبد خرید' => null]" />

    <h1 class="mb-6 text-2xl font-extrabold text-navy-900">سبد خرید</h1>

    @if(session('success'))
        <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($summary->isEmpty())
        <div class="card grid place-items-center gap-4 py-20 text-center">
            <svg class="size-16 text-navy-100" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272"/>
            </svg>
            <p class="text-sm font-semibold text-navy-700">سبد خرید شما خالی است.</p>
            <a href="{{ route('shop.index') }}" class="btn-primary">شروع خرید</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="card divide-y divide-navy-50">
                @foreach($summary->lines as $line)
                    @php $product = $line->item->product; @endphp
                    <div class="flex gap-4 p-5">
                        <a href="{{ route('shop.product', $product) }}"
                           class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-xl bg-slate-50">
                            @if($product->primary_image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->primary_image) }}"
                                     alt="{{ $product->name }}" class="size-full object-contain p-2">
                            @else
                                <span class="text-navy-200 text-xs">بدون تصویر</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <h2 class="line-clamp-2 text-sm font-bold leading-7 text-navy-900">
                                <a href="{{ route('shop.product', $product) }}" class="transition hover:text-electric-600">{{ $product->name }}</a>
                            </h2>
                            @if($line->item->variant)
                                <p class="mt-1 text-xs text-navy-400">{{ $line->item->variant->label() }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <form action="{{ route('cart.update', $line->item) }}" method="post" class="flex items-center gap-1.5">
                                    @csrf @method('PATCH')
                                    <input type="number" name="qty" value="{{ $line->item->qty }}" min="0" max="999"
                                           class="input w-20 !py-1.5 text-center text-sm nums-fa"
                                           onchange="this.form.submit()">
                                    <span class="text-xs text-navy-400">عدد</span>
                                </form>

                                <div class="text-left">
                                    <div class="text-sm font-extrabold text-navy-900 nums-fa">
                                        {{ \App\Support\Money::format($line->lineTotal) }}
                                    </div>
                                    @if($line->item->qty > 1)
                                        <div class="mt-0.5 text-[11px] text-navy-400 nums-fa">
                                            واحدی {{ \App\Support\Money::format($line->unitPrice) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('cart.remove', $line->item) }}" method="post">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-navy-300 transition hover:text-rose-500" aria-label="حذف">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <aside class="lg:sticky lg:top-28 lg:h-fit">
                <div class="card space-y-4 p-6">
                    <h2 class="text-sm font-bold text-navy-900">خلاصه سفارش</h2>

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-navy-500">تعداد اقلام</dt>
                            <dd class="font-medium text-navy-900 nums-fa">
                                {{ \App\Support\Digits::toPersian((string) $summary->itemCount()) }} عدد
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-navy-500">جمع کالاها</dt>
                            <dd class="font-medium text-navy-900 nums-fa">{{ \App\Support\Money::format($summary->subtotal()) }}</dd>
                        </div>
                        @if($summary->savings() > 0)
                            <div class="flex justify-between text-rose-600">
                                <dt>سود شما از خرید</dt>
                                <dd class="font-medium nums-fa">{{ \App\Support\Money::format($summary->savings()) }}</dd>
                            </div>
                        @endif
                        @if($summary->loyaltyDiscount() > 0)
                            <div class="flex justify-between text-gold-700">
                                <dt>تخفیف باشگاه مشتریان</dt>
                                <dd class="font-medium nums-fa">{{ \App\Support\Money::format($summary->loyaltyDiscount()) }}</dd>
                            </div>
                        @endif
                    </dl>

                    {{-- کد تخفیف --}}
                    <div class="border-t border-navy-100 pt-4">
                        @if($coupon = $summary->cart?->coupon)
                            <div class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2.5">
                                <div class="text-xs">
                                    <div class="font-bold text-emerald-800" dir="ltr">{{ $coupon->code }}</div>
                                    <div class="mt-0.5 text-emerald-600 nums-fa">
                                        {{ \App\Support\Money::format($summary->couponDiscount()) }} تخفیف
                                    </div>
                                </div>
                                <form action="{{ route('cart.coupon.remove') }}" method="post">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600">حذف</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('cart.coupon.apply') }}" method="post" class="flex gap-2">
                                @csrf
                                <input type="text" name="code" dir="ltr" class="input !py-2.5 !text-xs" placeholder="کد تخفیف">
                                <button type="submit" class="btn-ghost shrink-0 !px-4 !py-2.5 !text-xs">اعمال</button>
                            </form>
                        @endif

                        @error('coupon')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between border-t border-navy-100 pt-4">
                        <span class="text-sm font-bold text-navy-900">مبلغ قابل پرداخت</span>
                        <span class="text-lg font-extrabold text-navy-900 nums-fa">
                            {{ \App\Support\Money::format($summary->payable()) }}
                        </span>
                    </div>

                    <p class="text-[11px] leading-6 text-navy-400">هزینه ارسال در مرحله بعد و بر اساس استان و وزن سفارش محاسبه می‌شود.</p>

                    @auth('customer')
                        <a href="{{ route('checkout.index') }}" class="btn-primary w-full">ادامه و تسویه حساب</a>
                    @else
                        <a href="{{ route('auth.login') }}" class="btn-primary w-full">ورود و ادامه خرید</a>
                        <p class="text-center text-[11px] text-navy-400">برای تکمیل سفارش باید وارد حساب شوید.</p>
                    @endauth
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection
