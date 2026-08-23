@extends('admin.layouts.app')
@section('heading', 'محصولات')

@section('content')
<div class="space-y-5">
    <div class="card flex flex-wrap items-center gap-3 p-4">
        <form method="get" class="flex flex-1 flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ request('q') }}" class="input max-w-xs !py-2.5" placeholder="نام یا کد کالا">
            <select name="status" class="input max-w-[160px] !py-2.5">
                <option value="">همه وضعیت‌ها</option>
                <option value="published" @selected(request('status')==='published')>منتشرشده</option>
                <option value="draft" @selected(request('status')==='draft')>پیش‌نویس</option>
                <option value="archived" @selected(request('status')==='archived')>بایگانی</option>
            </select>
            <select name="category" class="input max-w-[200px] !py-2.5">
                <option value="">همه دسته‌ها</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary !py-2.5 !text-xs">اعمال</button>
        </form>

        <a href="{{ route('admin.products.create') }}" class="btn-navy !py-2.5 !text-xs">افزودن محصول</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="p-4 font-medium">محصول</th>
                    <th class="p-4 font-medium">دسته</th>
                    <th class="p-4 font-medium">قیمت خرده</th>
                    <th class="p-4 font-medium">موجودی</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse($products as $product)
                    @php $retail = app(\App\Services\Pricing\PriceResolver::class)->retailFor($product); @endphp
                    <tr class="transition hover:bg-slate-50">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-100">
                                    @if($product->primary_image)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->primary_image) }}" alt="" class="size-full object-contain p-1">
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <div class="line-clamp-1 font-medium text-navy-900">{{ $product->name }}</div>
                                    <div class="mt-0.5 text-xs text-navy-400 nums-fa" dir="ltr">{{ $product->sku }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-xs text-navy-500">{{ $product->category?->name ?: '—' }}</td>
                        <td class="p-4 font-medium text-navy-900 nums-fa">
                            {{ $retail ? \App\Support\Money::format($retail->amount, false) : '—' }}
                        </td>
                        <td class="p-4">
                            <span class="badge nums-fa {{ $product->stock <= 3 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ \App\Support\Digits::toPersian((string) $product->stock) }}
                            </span>
                        </td>
                        <td class="p-4"><x-admin.status-badge :status="$product->status" /></td>
                        <td class="p-4 text-left">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-xs font-semibold text-electric-600">ویرایش</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-12 text-center text-navy-400">محصولی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())<div>{{ $products->links() }}</div>@endif
</div>
@endsection
