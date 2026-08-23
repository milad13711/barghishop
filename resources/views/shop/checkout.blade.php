@extends('layouts.app')

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="['سبد خرید' => route('cart.index'), 'تسویه حساب' => null]" />

    <h1 class="mb-6 text-2xl font-extrabold text-navy-900">تسویه حساب</h1>

    @if($errors->any())
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @if(session('success'))
        <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-5">

            {{-- آدرس --}}
            <section class="card p-6" x-data="{ adding: {{ $addresses->isEmpty() ? 'true' : 'false' }} }">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-navy-900">آدرس تحویل</h2>
                    <button type="button" @click="adding = !adding" class="text-xs font-semibold text-electric-600">
                        <span x-show="!adding">افزودن آدرس جدید</span>
                        <span x-show="adding" x-cloak>انصراف</span>
                    </button>
                </div>

                <div x-show="!adding" class="space-y-3">
                    @foreach($addresses as $item)
                        <label class="flex cursor-pointer gap-3 rounded-xl p-4 ring-1 transition
                                      {{ $item->id === $address?->id ? 'bg-electric-50 ring-electric-300' : 'ring-navy-100 hover:bg-slate-50' }}">
                            <input type="radio" name="address_id" value="{{ $item->id }}" form="place-order"
                                   @checked($item->id === $address?->id)
                                   class="mt-1 size-4 text-electric-500 focus:ring-electric-500">
                            <div class="text-sm">
                                <div class="font-bold text-navy-900">{{ $item->receiver_name }}</div>
                                <div class="mt-1 leading-7 text-navy-500">{{ $item->fullText() }}</div>
                                <div class="mt-1 text-xs text-navy-400 nums-fa" dir="ltr">
                                    {{ \App\Support\Digits::toPersian($item->receiver_mobile) }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <form action="{{ route('checkout.address') }}" method="post" x-show="adding" x-cloak
                      class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">نام گیرنده</label>
                        <input type="text" name="receiver_name" class="input" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">موبایل گیرنده</label>
                        <input type="tel" name="receiver_mobile" dir="ltr" class="input" required>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">استان</label>
                        <select name="province_id" class="input" required>
                            @foreach($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-semibold text-navy-700">شهر</label>
                        <input type="text" name="city_name" class="input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-xs font-semibold text-navy-700">نشانی کامل</label>
                        <textarea name="line" rows="2" class="input" required></textarea>
                    </div>
                    <div><label class="mb-2 block text-xs font-semibold text-navy-700">پلاک</label><input type="text" name="plaque" class="input"></div>
                    <div><label class="mb-2 block text-xs font-semibold text-navy-700">واحد</label><input type="text" name="unit" class="input"></div>
                    <div><label class="mb-2 block text-xs font-semibold text-navy-700">کد پستی</label><input type="text" name="postal_code" dir="ltr" class="input"></div>
                    <div class="sm:col-span-2"><button type="submit" class="btn-primary">ثبت آدرس</button></div>
                </form>
            </section>

            {{-- روش ارسال --}}
            <section class="card p-6">
                <h2 class="mb-4 text-sm font-bold text-navy-900">روش ارسال</h2>

                @if($quotes->isEmpty())
                    <p class="text-sm text-navy-400">ابتدا آدرس تحویل را مشخص کنید.</p>
                @else
                    <div class="space-y-3">
                        @foreach($quotes as $quote)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl p-4 ring-1 ring-navy-100 transition hover:bg-slate-50">
                                <input type="radio" name="shipping_method_id" value="{{ $quote->method->id }}"
                                       form="place-order" @checked($loop->first)
                                       class="size-4 text-electric-500 focus:ring-electric-500">
                                <div class="flex-1">
                                    <div class="text-sm font-bold text-navy-900">{{ $quote->method->name }}</div>
                                    <div class="mt-0.5 text-xs text-navy-400">{{ $quote->method->description }} — {{ $quote->deliveryText() }}</div>
                                </div>
                                <div class="text-sm font-bold nums-fa {{ $quote->isFree ? 'text-emerald-600' : 'text-navy-900' }}">
                                    {{ $quote->isFree ? 'رایگان' : \App\Support\Money::format($quote->cost) }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- روش پرداخت --}}
            <section class="card p-6">
                <h2 class="mb-4 text-sm font-bold text-navy-900">روش پرداخت</h2>
                <div class="space-y-3">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl p-4 ring-1 ring-navy-100 transition hover:bg-slate-50">
                        <input type="radio" name="payment_method" value="online" form="place-order" checked
                               class="size-4 text-electric-500 focus:ring-electric-500">
                        <div>
                            <div class="text-sm font-bold text-navy-900">پرداخت اینترنتی (زرین‌پال)</div>
                            <div class="mt-0.5 text-xs text-navy-400">انتقال به درگاه بانکی امن</div>
                        </div>
                    </label>

                    @if(config('shop.payment.cod_enabled'))
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl p-4 ring-1 ring-navy-100 transition hover:bg-slate-50">
                            <input type="radio" name="payment_method" value="cod" form="place-order"
                                   class="size-4 text-electric-500 focus:ring-electric-500">
                            <div>
                                <div class="text-sm font-bold text-navy-900">پرداخت در محل</div>
                                <div class="mt-0.5 text-xs text-navy-400">پس‌کرایه؛ فقط برای برخی روش‌های ارسال</div>
                            </div>
                        </label>
                    @endif
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات سفارش (اختیاری)</label>
                    <textarea name="note" rows="2" class="input" form="place-order"
                              placeholder="مثلاً: لطفاً پیش از ارسال تماس بگیرید"></textarea>
                </div>
            </section>
        </div>

        <aside class="lg:sticky lg:top-28 lg:h-fit">
            <form id="place-order" action="{{ route('checkout.place') }}" method="post" class="card space-y-4 p-6">
                @csrf
                <h2 class="text-sm font-bold text-navy-900">خلاصه سفارش</h2>

                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-navy-500">جمع کالاها</dt>
                        <dd class="font-medium text-navy-900 nums-fa">{{ \App\Support\Money::format($summary->subtotal()) }}</dd>
                    </div>
                    @if($summary->discountTotal() > 0)
                        <div class="flex justify-between text-emerald-600">
                            <dt>تخفیف</dt>
                            <dd class="font-medium nums-fa">{{ \App\Support\Money::format($summary->discountTotal()) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-navy-500">وزن سفارش</dt>
                        <dd class="font-medium text-navy-900 nums-fa">
                            {{ \App\Support\Digits::toPersian((string) round($summary->weight() / 1000, 1)) }} کیلوگرم
                        </dd>
                    </div>
                </dl>

                <div class="flex items-center justify-between border-t border-navy-100 pt-4">
                    <span class="text-sm font-bold text-navy-900">جمع کالاها پس از تخفیف</span>
                    <span class="text-lg font-extrabold text-navy-900 nums-fa">{{ \App\Support\Money::format($summary->payable()) }}</span>
                </div>

                <p class="text-[11px] leading-6 text-navy-400">
                    هزینه ارسال بر اساس روش انتخابی به مبلغ نهایی اضافه و در صفحه پرداخت نمایش داده می‌شود.
                </p>

                <button type="submit" class="btn-primary w-full">ثبت سفارش و پرداخت</button>
            </form>
        </aside>
    </div>
</div>
@endsection
