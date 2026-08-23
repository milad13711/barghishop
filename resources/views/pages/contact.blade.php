@extends('layouts.app')

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="['تماس با ما' => null]" />

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="card p-6 lg:p-8">
            <h1 class="text-xl font-extrabold text-navy-900">پیام خود را بفرستید</h1>
            <p class="mt-2 text-sm text-navy-500">در سریع‌ترین زمان ممکن پاسخ شما را می‌دهیم.</p>

            @if(session('success'))
                <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('pages.contact.store') }}" method="post" class="mt-6 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">نام و نام خانوادگی</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="input" required>
                    @error('name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">شماره تماس</label>
                    <input type="tel" name="mobile" value="{{ old('mobile') }}" class="input" dir="ltr">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-navy-700">موضوع</label>
                    <input type="text" name="subject" value="{{ old('subject') }}" class="input">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-xs font-semibold text-navy-700">متن پیام</label>
                    <textarea name="body" rows="5" class="input" required>{{ old('body') }}</textarea>
                    @error('body')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary">ارسال پیام</button>
                </div>
            </form>
        </div>

        <aside class="card h-fit space-y-5 p-6">
            <h2 class="text-sm font-bold text-navy-900">راه‌های ارتباطی</h2>
            @foreach([
                ['تلفن فروشگاه', \App\Models\Setting::get('support_phone')],
                ['موبایل / واتساپ', \App\Models\Setting::get('support_mobile')],
                ['ساعت کاری', \App\Models\Setting::get('working_hours')],
                ['نشانی', \App\Models\Setting::get('address')],
            ] as [$label, $value])
                <div class="border-b border-navy-50 pb-4 last:border-0 last:pb-0">
                    <div class="text-xs text-navy-400">{{ $label }}</div>
                    <div class="mt-1 text-sm font-semibold text-navy-900 nums-fa">{{ \App\Support\Digits::toPersian((string) $value) }}</div>
                </div>
            @endforeach
        </aside>
    </div>
</div>
@endsection
