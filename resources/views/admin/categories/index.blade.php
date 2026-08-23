@extends('admin.layouts.app')
@section('heading', 'دسته‌بندی‌ها')

@section('content')
<div class="grid gap-5 lg:grid-cols-[1fr_340px]">
    <div class="card overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="p-4 font-medium">نام</th>
                    <th class="p-4 font-medium">والد</th>
                    <th class="p-4 font-medium">محصولات</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @foreach($categories as $category)
                    <tr>
                        <td class="p-4 font-medium text-navy-900">{{ $category->name }}</td>
                        <td class="p-4 text-xs text-navy-500">{{ $category->parent?->name ?: '—' }}</td>
                        <td class="p-4 text-xs text-navy-500 nums-fa">{{ \App\Support\Digits::toPersian((string) $category->products_count) }}</td>
                        <td class="p-4">
                            <span class="badge {{ $category->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td class="p-4 text-left">
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="post"
                                  onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-rose-600">حذف</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card h-fit space-y-4 p-5">
        <h2 class="text-sm font-bold text-navy-900">افزودن دسته‌بندی</h2>
        <form action="{{ route('admin.categories.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">نام *</label>
                <input type="text" name="name" class="input !py-2.5" required>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">دسته والد</label>
                <select name="parent_id" class="input !py-2.5">
                    <option value="">— دسته اصلی —</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات</label>
                <textarea name="description" rows="3" class="input !py-2.5"></textarea>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">عنوان سئو</label>
                <input type="text" name="seo_title" class="input !py-2.5">
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-xs text-navy-600">
                <input type="checkbox" name="prices_require_login" value="1" class="size-4 rounded border-navy-200 text-electric-500">
                قیمت این دسته فقط برای کاربران وارد شده
            </label>
            <button type="submit" class="btn-primary w-full !py-2.5 !text-xs">افزودن</button>
        </form>
    </div>
</div>
@endsection
