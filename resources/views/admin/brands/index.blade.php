@extends('admin.layouts.app')
@section('heading', 'برندها')

@section('content')
<div class="grid gap-5 lg:grid-cols-[1fr_340px]">
    <div class="card overflow-x-auto">
        <table class="w-full min-w-[420px] text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="p-4 font-medium">نام</th>
                    <th class="p-4 font-medium">محصولات</th>
                    <th class="p-4 font-medium">وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @foreach($brands as $brand)
                    <tr>
                        <td class="p-4 font-medium text-navy-900">{{ $brand->name }}</td>
                        <td class="p-4 text-xs text-navy-500 nums-fa">{{ \App\Support\Digits::toPersian((string) $brand->products_count) }}</td>
                        <td class="p-4">
                            <span class="badge {{ $brand->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $brand->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card h-fit space-y-4 p-5">
        <h2 class="text-sm font-bold text-navy-900">افزودن برند</h2>
        <form action="{{ route('admin.brands.store') }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">نام *</label>
                <input type="text" name="name" class="input !py-2.5" required>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات</label>
                <textarea name="description" rows="3" class="input !py-2.5"></textarea>
            </div>
            <button type="submit" class="btn-primary w-full !py-2.5 !text-xs">افزودن</button>
        </form>
    </div>
</div>
@endsection
