@props(['product'])

@php
    $resolver = app(\App\Services\Pricing\PriceResolver::class);
    $customer = auth('customer')->user();
    $resolved  = $resolver->for($product, $customer);
    $retail    = $customer?->isWholesaler() ? $resolver->retailFor($product) : null;
    $image     = $product->primary_image;
@endphp

<article class="card card-hover group flex h-full flex-col overflow-hidden">
    <a href="{{ route('shop.product', $product) }}" class="relative block aspect-square overflow-hidden bg-slate-50">
        @if($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}"
                 alt="{{ $product->name }}" loading="lazy" width="400" height="400"
                 class="size-full object-contain p-4 transition duration-500 group-hover:scale-105">
        @else
            <div class="grid size-full place-items-center text-navy-200">
                <svg class="size-16" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18 8.25h.008v.008H18V8.25Zm2.25 10.5H3.75A2.25 2.25 0 0 1 1.5 16.5V7.5a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 7.5v9a2.25 2.25 0 0 1-2.25 2.25Z"/>
                </svg>
            </div>
        @endif

        <div class="absolute right-3 top-3 flex flex-col gap-1.5">
            @if($resolved->hasDiscount() && ! $resolved->hidden)
                <span class="badge bg-rose-500 text-white nums-fa shadow-sm">
                    {{ \App\Support\Digits::toPersian((string) $resolved->discountPercent()) }}٪
                </span>
            @endif
            @if($product->is_featured)
                <span class="badge bg-gold-500 text-navy-950 shadow-sm">ویژه</span>
            @endif
        </div>

        @unless($product->isAvailable())
            <div class="absolute inset-0 grid place-items-center bg-white/70 backdrop-blur-[2px]">
                <span class="badge bg-navy-900 text-white">{{ __('shop.out_of_stock') }}</span>
            </div>
        @endunless
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if($product->brand)
            <span class="text-[11px] font-semibold text-electric-600">{{ $product->brand->name }}</span>
        @endif

        <h3 class="mt-1 line-clamp-2 text-sm font-bold leading-6 text-navy-900">
            <a href="{{ route('shop.product', $product) }}" class="transition hover:text-electric-600">{{ $product->name }}</a>
        </h3>

        @if($product->warranty_months)
            <div class="mt-2 flex items-center gap-1 text-[11px] text-navy-400">
                <svg class="size-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span class="nums-fa">{{ \App\Support\Digits::toPersian((string) $product->warranty_months) }} {{ __('shop.months') }} {{ __('shop.warranty') }}</span>
            </div>
        @endif

        <div class="mt-auto pt-4">
            <x-price :resolved="$resolved" :retail="$retail" size="md" />

            <form action="{{ route('cart.add') }}" method="post" class="mt-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="btn-primary w-full !py-2.5 !text-xs"
                        @disabled(! $product->isAvailable() || $resolved->hidden)>
                    @if($resolved->hidden)
                        {{ __('shop.login_to_see') }}
                    @elseif($product->isAvailable())
                        {{ __('shop.add_to_cart') }}
                    @else
                        {{ __('shop.out_of_stock') }}
                    @endif
                </button>
            </form>
        </div>
    </div>
</article>
