@extends('admin.layouts.app')
@section('heading', $product->exists ? 'ویرایش محصول' : 'افزودن محصول')

@section('content')
@php
    $action = $product->exists ? route('admin.products.update', $product) : route('admin.products.store');
    $existingPrices = $product->exists
        ? $product->prices->groupBy('price_tier_id')
        : collect();
@endphp

<form action="{{ $action }}" method="post" enctype="multipart/form-data" class="grid gap-5 lg:grid-cols-[1fr_340px]"
      x-data="{ specs: {{ $product->specs->count() ?: 1 }} }">
    @csrf

    <div class="space-y-5">
        {{-- اطلاعات پایه --}}
        <section class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">اطلاعات محصول</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-navy-700">نام محصول *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="input" required>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">کد کالا (SKU) *</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" dir="ltr" class="input" required>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">برند</label>
                    <select name="brand_id" class="input">
                        <option value="">—</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">دسته‌بندی</label>
                    <select name="category_id" class="input">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">وضعیت</label>
                    <select name="status" class="input">
                        @foreach(['published' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'archived' => 'بایگانی'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $product->status ?: 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-navy-700">توضیح کوتاه</label>
                    <textarea name="short_description" rows="2" class="input">{{ old('short_description', $product->short_description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات کامل (HTML مجاز است)</label>
                    <textarea name="body" rows="10" class="input font-mono !text-xs" dir="auto">{{ old('body', $product->body) }}</textarea>
                </div>
            </div>
        </section>

        {{-- مشخصات فنی --}}
        <section class="card space-y-4 p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-navy-900">مشخصات فنی</h2>
                <button type="button" @click="specs++" class="text-xs font-semibold text-electric-600">+ افزودن ردیف</button>
            </div>

            <template x-for="i in specs" :key="i">
                <div class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                    <input type="text" :name="`specs[${i-1}][key]`" class="input !py-2.5 !text-xs" placeholder="عنوان (مثلاً اندازه نمایشگر)">
                    <input type="text" :name="`specs[${i-1}][value]`" class="input !py-2.5 !text-xs" placeholder="مقدار (مثلاً ۷ اینچ)">
                    <label class="flex items-center gap-1.5 px-2 text-[11px] text-navy-500">
                        <input type="checkbox" :name="`specs[${i-1}][is_filterable]`" value="1"
                               class="size-4 rounded border-navy-200 text-electric-500">
                        فیلتر
                    </label>
                </div>
            </template>

            @if($product->specs->isNotEmpty())
                <p class="text-[11px] text-navy-400">
                    مقادیر فعلی پایین آمده‌اند؛ برای حفظ آن‌ها دوباره واردشان کنید (ذخیره، مشخصات را جایگزین می‌کند).
                </p>
                <div class="rounded-xl bg-slate-50 p-3">
                    @foreach($product->specs as $spec)
                        <div class="flex justify-between border-b border-navy-100 py-1.5 text-xs last:border-0">
                            <span class="text-navy-500">{{ $spec->key }}</span>
                            <span class="font-medium text-navy-800">{{ $spec->value }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- قیمت‌ها --}}
        <section class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">قیمت‌گذاری <span class="font-normal text-navy-400">(به تومان)</span></h2>

            @foreach($tiers as $tier)
                @php $rows = $existingPrices->get($tier->id, collect())->sortBy('min_qty')->values(); @endphp
                <div class="rounded-xl bg-slate-50 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="text-xs font-bold text-navy-900">{{ $tier->name }}</span>
                        @if($tier->is_wholesale)
                            <span class="badge bg-gold-100 text-gold-800">عمده</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        @foreach(range(0, 2) as $i)
                            @php $row = $rows->get($i); @endphp
                            <div class="grid gap-2 sm:grid-cols-3">
                                <input type="number" name="prices[{{ $tier->id }}][{{ $i }}][min_qty]" min="1"
                                       value="{{ $row->min_qty ?? ($i === 0 ? 1 : '') }}"
                                       class="input !py-2 !text-xs" placeholder="حداقل تعداد">
                                <input type="text" name="prices[{{ $tier->id }}][{{ $i }}][amount]" dir="ltr"
                                       value="{{ $row ? \App\Support\Money::toToman($row->amount) : '' }}"
                                       class="input !py-2 !text-xs" placeholder="قیمت فروش (تومان)">
                                <input type="text" name="prices[{{ $tier->id }}][{{ $i }}][compare_at]" dir="ltr"
                                       value="{{ $row?->compare_at ? \App\Support\Money::toToman($row->compare_at) : '' }}"
                                       class="input !py-2 !text-xs" placeholder="قیمت قبل از تخفیف">
                            </div>
                        @endforeach
                    </div>

                    @if($tier->is_wholesale && $tier->fallback_discount_percent > 0)
                        <p class="mt-2 text-[11px] text-navy-400 nums-fa">
                            اگر خالی بماند، خودکار {{ \App\Support\Digits::toPersian((string) (int) $tier->fallback_discount_percent) }}٪ کمتر از قیمت خرده محاسبه می‌شود.
                        </p>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- سئو --}}
        <section class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">سئو</h2>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">عنوان سئو</label>
                <input type="text" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}" class="input">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات متا</label>
                <textarea name="seo_description" rows="2" class="input">{{ old('seo_description', $product->seo_description) }}</textarea>
            </div>
        </section>
    </div>

    <aside class="space-y-5">
        <div class="card space-y-4 p-5">
            <button type="submit" class="btn-primary w-full">{{ $product->exists ? 'ذخیره تغییرات' : 'ساخت محصول' }}</button>

            @if($product->exists)
                <a href="{{ route('shop.product', $product) }}" target="_blank" class="btn-ghost w-full !py-2.5 !text-xs">مشاهده در سایت</a>
            @endif
        </div>

        <div class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">موجودی و انبار</h2>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">موجودی</label>
                <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" class="input !py-2.5">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">وزن (گرم)</label>
                <input type="number" name="weight_grams" value="{{ old('weight_grams', $product->weight_grams ?? 1000) }}" class="input !py-2.5">
                <p class="mt-1.5 text-[11px] text-navy-400">در محاسبه هزینه ارسال وزنی استفاده می‌شود.</p>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">گارانتی (ماه)</label>
                <input type="number" name="warranty_months" value="{{ old('warranty_months', $product->warranty_months ?? 0) }}" class="input !py-2.5">
            </div>

            @foreach([
                ['track_stock', 'کنترل موجودی', $product->track_stock ?? true],
                ['allow_backorder', 'فروش با موجودی صفر', $product->allow_backorder ?? false],
                ['is_featured', 'محصول ویژه', $product->is_featured ?? false],
                ['prices_require_login', 'قیمت فقط برای کاربران وارد شده', $product->prices_require_login ?? false],
            ] as [$field, $label, $checked])
                <label class="flex cursor-pointer items-center gap-2 text-xs text-navy-600">
                    <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $checked))
                           class="size-4 rounded border-navy-200 text-electric-500 focus:ring-electric-500">
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <div class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">تصاویر</h2>
            <input type="file" name="images[]" multiple accept="image/*"
                   class="w-full text-xs text-navy-600 file:ml-3 file:rounded-lg file:border-0 file:bg-navy-900 file:px-3 file:py-2 file:text-xs file:text-white">

            @if($product->exists && $product->media->isNotEmpty())
                <div class="grid grid-cols-3 gap-2">
                    @foreach($product->media as $media)
                        <div class="group relative aspect-square overflow-hidden rounded-lg bg-slate-100">
                            <img src="{{ $media->url() }}" alt="" class="size-full object-contain p-1">
                            @if($media->is_primary)
                                <span class="absolute right-1 top-1 badge bg-gold-500 !px-1.5 !py-0.5 text-[9px] text-navy-950">اصلی</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($product->exists)
            <div class="card p-5">
                <button type="submit" form="delete-product" class="w-full rounded-xl px-4 py-2.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                        onclick="return confirm('این محصول حذف شود؟')">حذف محصول</button>
            </div>
        @endif
    </aside>
</form>

@if($product->exists)
    <form id="delete-product" action="{{ route('admin.products.destroy', $product) }}" method="post" class="hidden">
        @csrf @method('DELETE')
    </form>
@endif
@endsection
