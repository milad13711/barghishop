@extends('admin.layouts.app')
@section('heading', 'مقالات')

@section('content')
<div class="space-y-5">
    <div class="card flex flex-wrap items-center gap-3 p-4">
        <form method="get" class="flex flex-1 items-center gap-3">
            <input type="search" name="q" value="{{ request('q') }}" class="input max-w-xs !py-2.5" placeholder="عنوان مقاله">
            <button type="submit" class="btn-primary !py-2.5 !text-xs">جستجو</button>
        </form>
        <a href="{{ route('admin.posts.create') }}" class="btn-navy !py-2.5 !text-xs">نوشتن مقاله</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="p-4 font-medium">عنوان</th>
                    <th class="p-4 font-medium">دسته</th>
                    <th class="p-4 font-medium">بازدید</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse($posts as $post)
                    <tr>
                        <td class="p-4"><div class="line-clamp-1 font-medium text-navy-900">{{ $post->title }}</div></td>
                        <td class="p-4 text-xs text-navy-500">{{ $post->category?->name ?: '—' }}</td>
                        <td class="p-4 text-xs text-navy-500 nums-fa">{{ \App\Support\Digits::toPersian((string) $post->view_count) }}</td>
                        <td class="p-4"><x-admin.status-badge :status="$post->status" /></td>
                        <td class="p-4 text-left">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="text-xs font-semibold text-electric-600">ویرایش</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-12 text-center text-navy-400">مقاله‌ای یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($posts->hasPages())<div>{{ $posts->links() }}</div>@endif
</div>
@endsection
