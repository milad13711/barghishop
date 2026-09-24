@extends('layouts.app')

@push('schema')
    {!! \App\Support\Seo\Schema::render($schema) !!}
@endpush

@section('content')
<div class="container-app py-6">

    <x-breadcrumbs :items="array_filter([
        $product->category?->parent?->name => $product->category?->parent ? route('shop.category', $product->category->parent) : null,
        $product->category?->name => $product->category ? route('shop.category', $product->category) : null,
        $product->name => null,
    ], fn ($v, $k) => filled($k), ARRAY_FILTER_USE_BOTH)" />

    @php
        $generalImages = $product->media->map(fn ($m) => ['src' => $m->url(), 'alt' => $m->alt ?: $product->name])->values();
        $defaultVariantImages = collect($variantData['variants'])->firstWhere('id', $variantData['initial'])['images'] ?? [];
        // مدل پیش‌فرض تصویر مخصوص دارد؟ همان؛ وگرنه گالری عمومی
        $galleryImages = count($defaultVariantImages) ? collect($defaultVariantImages) : $generalImages;
        $hasAnyImages = $generalImages->isNotEmpty() || collect($variantData['variants'])->contains(fn ($v) => count($v['images']));
        $buyConfig = ['variants' => $variantData['variants'], 'groups' => $variantData['groups'], 'initial' => $variantData['initial'], 'general' => $generalImages];
    @endphp

    <div class="grid gap-8 lg:grid-cols-[1fr_380px]" x-data="productBuy({{ \Illuminate\Support\Js::from($buyConfig) }})">

        {{-- تصویر و اطلاعات --}}
        <div class="grid gap-8 md:grid-cols-2">
            <div x-data="productGallery({{ \Illuminate\Support\Js::from($galleryImages) }})"
                 x-on:variant-images.window="setImages($event.detail)">
                @if($hasAnyImages)
                    <div class="card relative aspect-square cursor-zoom-in select-none overflow-hidden bg-white" x-show="images.length"
                         tabindex="0" role="button" aria-label="نمایش بزرگ‌تر تصویر"
                         @mousemove="hoverMove($event)" @mouseleave="hoverLeave()" @click="open()" @keydown.enter="open()"
                         @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)">
                        <img src="{{ $galleryImages[0]['src'] ?? '' }}" :src="current?.src" :alt="current?.alt ?? ''"
                             width="600" height="600" draggable="false"
                             class="absolute inset-0 size-full object-contain p-6 transition-transform duration-150 ease-out will-change-transform"
                             :style="zoom ? `transform: scale(2.4); transform-origin: ${ox}% ${oy}%` : ''">

                        <template x-if="images.length > 1">
                            <div>
                                <button type="button" @click.stop="next()" aria-label="تصویر بعدی"
                                        class="absolute left-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-navy-700 shadow ring-1 ring-navy-100 transition hover:bg-white">
                                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                </button>
                                <button type="button" @click.stop="prev()" aria-label="تصویر قبلی"
                                        class="absolute right-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-navy-700 shadow ring-1 ring-navy-100 transition hover:bg-white">
                                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                </button>
                                <span class="absolute bottom-3 left-3 rounded-lg bg-navy-900/70 px-2 py-1 text-[11px] text-white nums-fa"
                                      x-text="(index + 1).toLocaleString('fa-IR') + ' / ' + images.length.toLocaleString('fa-IR')"></span>
                            </div>
                        </template>

                        <span class="pointer-events-none absolute bottom-3 right-3 hidden items-center gap-1 rounded-lg bg-navy-900/70 px-2 py-1 text-[11px] text-white [@media(hover:hover)]:flex" x-show="!zoom" x-transition.opacity>
                            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/></svg>
                            ماوس را روی تصویر ببرید
                        </span>
                    </div>

                    <template x-if="images.length > 1">
                        <div class="mt-3 grid grid-cols-5 gap-2">
                            <template x-for="(img, i) in images" :key="i">
                                <button type="button" @click="go(i)" :aria-label="`نمایش تصویر ${i + 1}`"
                                        :class="index === i ? 'ring-2 ring-gold-500' : 'ring-1 ring-navy-100 hover:ring-navy-300'"
                                        class="aspect-square overflow-hidden rounded-xl bg-white p-1.5 transition">
                                    <img :src="img.src" alt="" class="size-full object-contain" loading="lazy">
                                </button>
                            </template>
                        </div>
                    </template>

                    {{-- نمایش تمام‌صفحه با زوم --}}
                    <template x-teleport="body">
                        <div x-show="lightbox" x-cloak x-transition.opacity
                             @keydown.escape.window="lightbox && close()"
                             @keydown.arrow-left.window="lightbox && next()"
                             @keydown.arrow-right.window="lightbox && prev()"
                             class="fixed inset-0 z-[90] flex flex-col bg-navy-950/95" role="dialog" aria-modal="true" aria-label="نمایش تصویر محصول">
                            <div class="flex shrink-0 items-center justify-between px-4 py-3 text-white">
                                <span class="text-xs text-navy-200 nums-fa" x-text="(index + 1).toLocaleString('fa-IR') + ' / ' + images.length.toLocaleString('fa-IR')"></span>
                                <span class="hidden text-xs text-navy-300 sm:block">برای زوم روی تصویر کلیک کنید و ماوس/انگشت را حرکت دهید</span>
                                <button type="button" @click="close()" class="rounded-lg p-2 hover:bg-white/10" aria-label="بستن">
                                    <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <div class="relative min-h-0 flex-1 overflow-hidden"
                                 @touchstart.passive="touchStart($event)" @touchend="touchEnd($event, true)">
                                <div class="absolute inset-0" :class="lbZoom ? 'cursor-zoom-out' : 'cursor-zoom-in'"
                                     @click="lbToggle($event)" @mousemove="lbMove($event)" @touchmove.prevent="lbMove($event)">
                                    <img :src="current?.src" :alt="current?.alt ?? ''" draggable="false"
                                         class="size-full select-none object-contain p-4 transition-transform duration-200 ease-out"
                                         :style="lbZoom ? `transform: scale(2.6); transform-origin: ${lbX}% ${lbY}%` : ''">
                                </div>
                                <template x-if="images.length > 1">
                                    <div>
                                        <button type="button" @click.stop="next()" aria-label="تصویر بعدی"
                                                class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white backdrop-blur hover:bg-white/20">
                                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                                        </button>
                                        <button type="button" @click.stop="prev()" aria-label="تصویر قبلی"
                                                class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white backdrop-blur hover:bg-white/20">
                                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <template x-if="images.length > 1">
                                <div class="flex shrink-0 justify-center gap-2 overflow-x-auto p-3">
                                    <template x-for="(img, i) in images" :key="i">
                                        <button type="button" @click="go(i)"
                                                :class="index === i ? 'ring-2 ring-gold-500' : 'opacity-60 hover:opacity-100'"
                                                class="size-14 shrink-0 overflow-hidden rounded-lg bg-white p-1 transition">
                                            <img :src="img.src" alt="" class="size-full object-contain">
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="!images.length" x-cloak class="card grid aspect-square place-items-center bg-white">
                        <svg class="size-28 text-navy-100" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 8.25h.008v.008H18V8.25Zm2.25 10.5H3.75A2.25 2.25 0 0 1 1.5 16.5V7.5a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 7.5v9a2.25 2.25 0 0 1-2.25 2.25Z"/></svg>
                    </div>
                @else
                    <div class="card grid aspect-square place-items-center bg-white">
                        <svg class="size-28 text-navy-100" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 8.25h.008v.008H18V8.25Zm2.25 10.5H3.75A2.25 2.25 0 0 1 1.5 16.5V7.5a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 7.5v9a2.25 2.25 0 0 1-2.25 2.25Z"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div>
                @if($product->brand)
                    <a href="{{ route('shop.brand', $product->brand) }}" class="badge bg-electric-50 text-electric-700">
                        {{ $product->brand->name }}
                    </a>
                @endif

                <h1 class="mt-3 text-xl font-extrabold leading-9 text-navy-900 lg:text-2xl">{{ $product->name }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-navy-400">
                    <span class="nums-fa">کد کالا: {{ \App\Support\Digits::toPersian($product->sku) }}</span>
                    @if($product->rating_count)
                        <span class="flex items-center gap-1 text-gold-600 nums-fa">
                            ★ {{ \App\Support\Digits::toPersian((string) round($product->rating_avg, 1)) }}
                            <span class="text-navy-400">({{ \App\Support\Digits::toPersian((string) $product->rating_count) }} دیدگاه)</span>
                        </span>
                    @endif
                </div>

                @if($product->short_description)
                    <p class="mt-5 text-sm leading-8 text-navy-600">{{ $product->short_description }}</p>
                @endif

                <ul class="mt-6 space-y-3">
                    @if($product->warranty_months)
                        <li class="flex items-center gap-2 text-sm text-navy-700">
                            <svg class="size-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <span class="nums-fa">{{ \App\Support\Digits::toPersian((string) $product->warranty_months) }} ماه گارانتی رسمی</span>
                        </li>
                    @endif
                    <li class="flex items-center gap-2 text-sm text-navy-700">
                        <svg class="size-5 text-electric-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                        </svg>
                        ارسال به سراسر ایران — پست، تیپاکس و پیک تهران
                    </li>
                    <li class="flex items-center gap-2 text-sm {{ $product->isAvailable() ? 'text-emerald-600' : 'text-rose-600' }}">
                        <span class="size-2 rounded-full {{ $product->isAvailable() ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        {{ $product->isAvailable() ? __('shop.in_stock') : __('shop.out_of_stock') }}
                    </li>
                </ul>
            </div>
        </div>

        {{-- جعبه خرید --}}
        <aside class="lg:sticky lg:top-28 lg:h-fit">
            <div class="card space-y-5 p-6">
                @if($variantData['variants'])
                    {{-- انتخاب مدل --}}
                    <div class="space-y-4">
                        <template x-if="groups.length">
                            <div class="space-y-4">
                                <template x-for="g in groups" :key="g.key">
                                    <div>
                                        <div class="mb-2 text-xs font-bold text-navy-800">
                                            <span x-text="g.key"></span>:
                                            <span class="font-medium text-navy-500" x-text="selected[g.key]"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="val in g.values" :key="val">
                                                <button type="button" @click="pick(g.key, val)"
                                                        :aria-pressed="selected[g.key] === val"
                                                        :class="[
                                                            selected[g.key] === val ? 'bg-navy-900 text-white ring-navy-900' : 'bg-white text-navy-700 ring-navy-200 hover:ring-navy-400',
                                                            valueAvailable(g.key, val) ? '' : 'opacity-50 line-through'
                                                        ]"
                                                        class="rounded-xl px-4 py-2 text-xs font-semibold ring-1 transition" x-text="val"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!groups.length">
                            <div>
                                <div class="mb-2 text-xs font-bold text-navy-800">مدل را انتخاب کنید</div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="v in variants" :key="v.id">
                                        <button type="button" @click="choose(v.id)" :aria-pressed="variantId === v.id"
                                                :class="[
                                                    variantId === v.id ? 'bg-navy-900 text-white ring-navy-900' : 'bg-white text-navy-700 ring-navy-200 hover:ring-navy-400',
                                                    v.available ? '' : 'opacity-50 line-through'
                                                ]"
                                                class="rounded-xl px-4 py-2 text-xs font-semibold ring-1 transition" x-text="v.label"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- قیمت و موجودی مدل انتخابی --}}
                    <div x-show="current" class="space-y-2">
                        <template x-if="current && current.hidden">
                            <a href="{{ route('auth.login') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-electric-600 hover:underline">{{ __('shop.login_to_see') }}</a>
                        </template>
                        <template x-if="current && !current.hidden && current.callFor">
                            <span class="text-sm font-semibold text-navy-400">{{ __('shop.call_for_price') }}</span>
                        </template>
                        <template x-if="current && !current.hidden && !current.callFor">
                            <div class="flex flex-col gap-0.5">
                                <div x-show="current.compare" class="flex items-center gap-2">
                                    <span class="badge bg-rose-50 text-rose-600 nums-fa" x-text="current.discount + '٪ تخفیف'"></span>
                                    <span class="text-base text-navy-300 line-through nums-fa" x-text="current.compare"></span>
                                </div>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-3xl font-extrabold text-navy-900 nums-fa" x-text="current.price"></span>
                                    <span class="text-xs font-medium text-navy-400">تومان</span>
                                </div>
                                <div x-show="current.tier" class="flex items-center gap-1.5 text-xs">
                                    <span class="badge bg-gold-50 text-gold-700" x-text="current.tier"></span>
                                    <span x-show="current.retail" class="text-navy-400 nums-fa" x-text="'{{ __('shop.retail_price') }}: ' + current.retail"></span>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center gap-2 text-xs">
                            <template x-if="current && current.available">
                                <span class="flex items-center gap-1.5 text-emerald-600"><span class="size-2 rounded-full bg-emerald-500"></span>
                                    <span x-text="current.stockText || '{{ __('shop.in_stock') }}'"></span></span>
                            </template>
                            <template x-if="current && !current.available">
                                <span class="flex items-center gap-1.5 text-rose-600"><span class="size-2 rounded-full bg-rose-500"></span>این مدل ناموجود است</span>
                            </template>
                        </div>

                        <div x-show="current && current.tierRows.length > 1" class="rounded-xl bg-gold-50 p-4">
                            <h3 class="mb-3 text-xs font-bold text-gold-800">قیمت پلکانی این مدل</h3>
                            <table class="w-full text-xs"><tbody class="divide-y divide-gold-200/60">
                                <template x-for="r in (current ? current.tierRows : [])" :key="r.min">
                                    <tr><td class="py-2 text-navy-600 nums-fa" x-text="'از ' + r.min + ' عدد'"></td>
                                        <td class="py-2 text-left font-bold text-navy-900 nums-fa" x-text="r.text"></td></tr>
                                </template>
                            </tbody></table>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">{{ session('success') }}</div>
                    @endif
                    @error('variant')
                        <div class="rounded-xl bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">{{ $message }}</div>
                    @enderror

                    <form action="{{ route('cart.add') }}" method="post" class="space-y-3">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" :value="variantId">
                        <input type="hidden" name="qty" :value="qty">

                        <div x-show="canBuy" class="flex items-center justify-between rounded-xl bg-slate-50 p-2">
                            <span class="px-2 text-xs font-medium text-navy-500">تعداد</span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="dec()" class="grid size-9 place-items-center rounded-lg bg-white text-navy-700 ring-1 ring-navy-100">−</button>
                                <span class="w-10 text-center text-sm font-bold nums-fa" x-text="qty.toLocaleString('fa-IR')">۱</span>
                                <button type="button" @click="inc()" class="grid size-9 place-items-center rounded-lg bg-white text-navy-700 ring-1 ring-navy-100">+</button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full" :disabled="!canBuy"
                                x-text="current && current.hidden ? '{{ __('shop.login_to_see') }}' : (current && !current.available ? '{{ __('shop.out_of_stock') }}' : '{{ __('shop.add_to_cart') }}')">
                            {{ __('shop.add_to_cart') }}
                        </button>
                    </form>
                @else

                <x-price :resolved="$resolved" :retail="$retail" size="lg" />

                @if($tierRows->isNotEmpty() && $tierRows->count() > 1)
                    <div class="rounded-xl bg-gold-50 p-4">
                        <h3 class="mb-3 text-xs font-bold text-gold-800">قیمت پلکانی {{ $resolved->tier->name }}</h3>
                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-gold-200/60">
                                @foreach($tierRows as $row)
                                    <tr>
                                        <td class="py-2 text-navy-600 nums-fa">از {{ \App\Support\Digits::toPersian((string) $row->min_qty) }} عدد</td>
                                        <td class="py-2 text-left font-bold text-navy-900 nums-fa">
                                            {{ \App\Support\Money::format($row->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if(session('success'))
                    <div class="rounded-xl bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('cart.add') }}" method="post" class="space-y-3" x-data="{ qty: 1 }">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="qty" :value="qty">

                    @if(! $resolved->hidden && $product->isAvailable())
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 p-2">
                            <span class="px-2 text-xs font-medium text-navy-500">تعداد</span>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="qty = Math.max(1, qty - 1)"
                                        class="grid size-9 place-items-center rounded-lg bg-white text-navy-700 ring-1 ring-navy-100">−</button>
                                <span class="w-10 text-center text-sm font-bold nums-fa"
                                      x-text="qty.toLocaleString('fa-IR')">۱</span>
                                <button type="button" @click="qty = qty + 1"
                                        class="grid size-9 place-items-center rounded-lg bg-white text-navy-700 ring-1 ring-navy-100">+</button>
                            </div>
                        </div>
                    @endif

                    <button type="submit" class="btn-primary w-full" @disabled(! $product->isAvailable() || $resolved->hidden)>
                        @if($resolved->hidden)
                            {{ __('shop.login_to_see') }}
                        @elseif($product->isAvailable())
                            {{ __('shop.add_to_cart') }}
                        @else
                            {{ __('shop.out_of_stock') }}
                        @endif
                    </button>
                </form>

                @endif

                @guest('customer')
                    <a href="{{ route('wholesale') }}" class="block rounded-xl bg-navy-50 p-4 text-center text-xs font-semibold text-navy-700 transition hover:bg-navy-100">
                        خرید عمده هستید؟ قیمت همکار بگیرید ←
                    </a>
                @endguest
            </div>
        </aside>
    </div>

    {{-- تب‌ها --}}
    <div class="mt-12" x-data="{ tab: 'specs' }">
        <div class="flex gap-1 border-b border-navy-100">
            @foreach(['specs' => __('shop.specs'), 'description' => __('shop.description'), 'reviews' => __('shop.reviews'), 'questions' => __('shop.questions')] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-electric-500 text-electric-600' : 'border-transparent text-navy-500 hover:text-navy-800'"
                        class="border-b-2 px-4 py-3 text-sm font-semibold transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="card mt-5 p-6">
            <div x-show="tab === 'specs'">
                @if($product->specs->isNotEmpty())
                    <dl class="divide-y divide-navy-50">
                        @foreach($product->specs as $spec)
                            <div class="grid grid-cols-3 gap-4 py-3 text-sm">
                                <dt class="text-navy-500">{{ $spec->key }}</dt>
                                <dd class="col-span-2 font-medium text-navy-900">{{ $spec->value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-sm text-navy-400">مشخصات فنی برای این محصول ثبت نشده است.</p>
                @endif
            </div>

            <div x-show="tab === 'description'" x-cloak class="prose-fa max-w-none text-sm">
                {!! $product->body ?: '<p>توضیحاتی ثبت نشده است.</p>' !!}
            </div>

            <div x-show="tab === 'reviews'" x-cloak>
                @forelse($reviews as $review)
                    <div class="border-b border-navy-50 py-4 last:border-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-navy-900">{{ $review->displayName() }}</span>
                            <span class="text-gold-500 text-xs nums-fa">{{ str_repeat('★', $review->rating) }}</span>
                        </div>
                        <p class="mt-2 text-sm leading-7 text-navy-600">{{ $review->body }}</p>
                    </div>
                @empty
                    <p class="text-sm text-navy-400">هنوز دیدگاهی ثبت نشده است. اولین نفر باشید.</p>
                @endforelse
            </div>

            <div x-show="tab === 'questions'" x-cloak>
                @forelse($questions as $question)
                    <div class="border-b border-navy-50 py-4 last:border-0">
                        <p class="text-sm font-bold text-navy-900">{{ $question->question }}</p>
                        <p class="mt-2 rounded-xl bg-slate-50 p-3 text-sm leading-7 text-navy-600">{{ $question->answer }}</p>
                    </div>
                @empty
                    <p class="text-sm text-navy-400">پرسشی ثبت نشده است.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if($related->isNotEmpty())
        <section class="mt-12">
            <x-section-heading :title="__('shop.related')" />
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach($related->take(4) as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
