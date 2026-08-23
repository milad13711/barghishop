@extends('admin.layouts.app')
@section('heading', $post->exists ? 'ویرایش مقاله' : 'مقاله جدید')

@section('content')
<form action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" method="post"
      class="grid gap-5 lg:grid-cols-[1fr_320px]" x-data="{ faq: {{ count($post->faq ?? []) ?: 1 }} }">
    @csrf

    <div class="space-y-5">
        <section class="card space-y-4 p-5">
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">عنوان *</label>
                <input type="text" name="title" value="{{ old('title', $post->title) }}" class="input" required>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">خلاصه</label>
                <textarea name="excerpt" rows="2" class="input">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">متن مقاله (HTML مجاز است)</label>
                <textarea name="body" rows="18" class="input font-mono !text-xs" dir="auto">{{ old('body', $post->body) }}</textarea>
            </div>
        </section>

        <section class="card space-y-4 p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-navy-900">پرسش‌های پرتکرار <span class="font-normal text-navy-400">(اسکیمای FAQ گوگل)</span></h2>
                <button type="button" @click="faq++" class="text-xs font-semibold text-electric-600">+ افزودن</button>
            </div>

            @foreach($post->faq ?? [] as $i => $item)
                <div class="space-y-2 rounded-xl bg-slate-50 p-3">
                    <input type="text" name="faq[{{ $i }}][q]" value="{{ $item['q'] }}" class="input !py-2 !text-xs" placeholder="پرسش">
                    <textarea name="faq[{{ $i }}][a]" rows="2" class="input !py-2 !text-xs" placeholder="پاسخ">{{ $item['a'] }}</textarea>
                </div>
            @endforeach

            <template x-for="i in faq" :key="i">
                <div class="space-y-2 rounded-xl bg-slate-50 p-3">
                    <input type="text" :name="`faq[${i + {{ count($post->faq ?? []) }} - 1}][q]`" class="input !py-2 !text-xs" placeholder="پرسش">
                    <textarea :name="`faq[${i + {{ count($post->faq ?? []) }} - 1}][a]`" rows="2" class="input !py-2 !text-xs" placeholder="پاسخ"></textarea>
                </div>
            </template>
        </section>
    </div>

    <aside class="space-y-5">
        <div class="card space-y-4 p-5">
            <button type="submit" class="btn-primary w-full">{{ $post->exists ? 'ذخیره' : 'ایجاد مقاله' }}</button>

            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">وضعیت</label>
                <select name="status" class="input !py-2.5">
                    <option value="draft" @selected(old('status', $post->status ?: 'draft') === 'draft')>پیش‌نویس</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>منتشرشده</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">دسته‌بندی</label>
                <select name="post_category_id" class="input !py-2.5">
                    <option value="">—</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('post_category_id', $post->post_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($post->exists)
                <a href="{{ route('blog.show', $post) }}" target="_blank" class="btn-ghost w-full !py-2.5 !text-xs">مشاهده در سایت</a>
            @endif
        </div>

        <div class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">سئو</h2>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">عنوان سئو</label>
                <input type="text" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}" class="input !py-2.5">
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات متا</label>
                <textarea name="seo_description" rows="3" class="input !py-2.5">{{ old('seo_description', $post->seo_description) }}</textarea>
            </div>
        </div>
    </aside>
</form>
@endsection
