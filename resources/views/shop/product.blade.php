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

    <div class="grid gap-8 lg:grid-cols-[1fr_380px]">

        {{-- تصویر و اطلاعات --}}
        <div class="grid gap-8 md:grid-cols-2">
            <div x-data="{ active: 0 }">
                <div class="card grid aspect-square place-items-center overflow-hidden bg-white p-6">
                    @if($product->media->isNotEmpty())
                        @foreach($product->media as $i => $media)
                            <img x-show="active === {{ $i }}" src="{{ $media->url() }}"
                                 alt="{{ $media->alt ?: $product->name }}" width="600" height="600"
                                 class="size-full object-contain">
                        @endforeach
                    @else
                        <svg class="size-28 text-navy-100" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 8.25h.008v.008H18V8.25Zm2.25 10.5H3.75A2.25 2.25 0 0 1 1.5 16.5V7.5a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 7.5v9a2.25 2.25 0 0 1-2.25 2.25Z"/>
                        </svg>
                    @endif
                </div>

                @if($product->media->count() > 1)
                    <div class="mt-3 grid grid-cols-5 gap-2">
                        @foreach($product->media as $i => $media)
                            <button type="button" @click="active = {{ $i }}"
                                    :class="active === {{ $i }} ? 'ring-2 ring-electric-500' : 'ring-1 ring-navy-100'"
                                    class="aspect-square overflow-hidden rounded-xl bg-white p-1.5">
                                <img src="{{ $media->url() }}" alt="" class="size-full object-contain">
                            </button>
                        @endforeach
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
