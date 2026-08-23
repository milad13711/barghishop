@extends('layouts.app')

@section('content')
<div class="container-app py-6">

    <x-breadcrumbs :items="array_filter([
        $category?->name => $category ? route('shop.category', $category) : null,
        $brand?->name => $brand ? route('shop.brand', $brand) : null,
        $searchTerm ? 'جستجوی «'.$searchTerm.'»' : null => null,
    ], fn ($v, $k) => filled($k), ARRAY_FILTER_USE_BOTH)" />

    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-navy-900">
            {{ $searchTerm ? 'نتایج جستجو برای «'.$searchTerm.'»' : ($category?->name ?? $brand?->name ?? 'همه محصولات') }}
        </h1>
        @if($category?->description)
            <p class="mt-3 max-w-3xl text-sm leading-7 text-navy-500">{{ $category->description }}</p>
        @endif
        <p class="mt-2 text-xs text-navy-400 nums-fa">
            {{ \App\Support\Digits::toPersian((string) $products->total()) }} کالا یافت شد
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">

        {{-- فیلترها --}}
        <aside x-data="{ open: false }" class="lg:block">
            <button type="button" @click="open = !open" class="btn-ghost w-full lg:hidden">
                {{ __('shop.filter') }}
            </button>

            <form method="get" x-show="open || window.innerWidth >= 1024" x-cloak
                  class="card mt-3 space-y-6 p-5 lg:mt-0 lg:!block">
                @if($searchTerm)<input type="hidden" name="q" value="{{ $searchTerm }}">@endif

                @if($subCategories->isNotEmpty())
                    <div>
                        <h3 class="mb-3 text-sm font-bold text-navy-900">{{ __('shop.categories') }}</h3>
                        <ul class="space-y-1.5 text-sm">
                            @foreach($subCategories as $sub)
                                <li>
                                    <a href="{{ route('shop.category', $sub) }}"
                                       class="block rounded-lg px-2 py-1.5 text-navy-600 transition hover:bg-navy-50 hover:text-navy-900">
                                        {{ $sub->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($brands->count() > 1)
                    <div>
                        <h3 class="mb-3 text-sm font-bold text-navy-900">{{ __('shop.brands') }}</h3>
                        <div class="space-y-2">
                            @foreach($brands as $b)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-navy-600">
                                    <input type="checkbox" name="brands[]" value="{{ $b->id }}"
                                           @checked(in_array((string) $b->id, (array) request('brands', [])))
                                           class="size-4 rounded border-navy-200 text-electric-500 focus:ring-electric-500">
                                    {{ $b->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach($facets as $key => $values)
                    <div>
                        <h3 class="mb-3 text-sm font-bold text-navy-900">{{ $key }}</h3>
                        <div class="space-y-2">
                            @foreach($values as $value)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-navy-600">
                                    <input type="checkbox" name="spec[{{ $key }}][]" value="{{ $value }}"
                                           @checked(in_array($value, (array) data_get(request('spec'), $key, [])))
                                           class="size-4 rounded border-navy-200 text-electric-500 focus:ring-electric-500">
                                    {{ $value }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <label class="flex cursor-pointer items-center gap-2 border-t border-navy-100 pt-4 text-sm font-medium text-navy-700">
                    <input type="checkbox" name="available" value="1" @checked(request()->boolean('available'))
                           class="size-4 rounded border-navy-200 text-electric-500 focus:ring-electric-500">
                    فقط کالاهای موجود
                </label>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 !py-2.5 !text-xs">اعمال فیلتر</button>
                    <a href="{{ url()->current() }}" class="btn-ghost !py-2.5 !text-xs">حذف</a>
                </div>
            </form>
        </aside>

        <div>
            {{-- مرتب‌سازی --}}
            <div class="card mb-5 flex flex-wrap items-center gap-2 p-3">
                <span class="px-2 text-xs font-semibold text-navy-400">{{ __('shop.sort') }}:</span>
                @foreach(['newest' => __('shop.newest'), 'bestseller' => __('shop.bestseller'), 'popular' => 'محبوب‌ترین'] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['sort' => $key]) }}"
                       class="rounded-lg px-3 py-1.5 text-xs font-medium transition
                              {{ request('sort', 'newest') === $key ? 'bg-navy-900 text-white' : 'text-navy-600 hover:bg-navy-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if($products->isEmpty())
                <div class="card grid place-items-center gap-3 py-20 text-center">
                    <svg class="size-14 text-navy-200" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <p class="text-sm font-semibold text-navy-700">{{ __('shop.no_results') }}</p>
                    <p class="text-xs text-navy-400">فیلترها را تغییر دهید یا عبارت دیگری جستجو کنید.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
