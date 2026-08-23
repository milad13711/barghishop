@extends('layouts.app')

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="['مقالات' => null]" />

    <header class="mb-8 max-w-2xl">
        <h1 class="text-2xl font-extrabold text-navy-900 lg:text-3xl">مقالات و راهنمای خرید</h1>
        <p class="mt-3 text-sm leading-8 text-navy-500">
            پیش از خرید بخوانید: مقایسه مدل‌ها، آموزش نصب، عیب‌یابی و نکات فنی تجهیزات برق ساختمان.
        </p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
        <aside>
            <div class="card p-5">
                <h2 class="mb-3 text-sm font-bold text-navy-900">دسته‌بندی مقالات</h2>
                <ul class="space-y-1.5 text-sm">
                    <li>
                        <a href="{{ route('blog.index') }}"
                           class="flex items-center justify-between rounded-lg px-2 py-1.5 transition
                                  {{ ! request('category') ? 'bg-navy-900 text-white' : 'text-navy-600 hover:bg-navy-50' }}">
                            همه مقالات
                        </a>
                    </li>
                    @foreach($categories as $category)
                        <li>
                            <a href="{{ route('blog.index', ['category' => $category->slug]) }}"
                               class="flex items-center justify-between rounded-lg px-2 py-1.5 transition
                                      {{ request('category') === $category->slug ? 'bg-navy-900 text-white' : 'text-navy-600 hover:bg-navy-50' }}">
                                <span>{{ $category->name }}</span>
                                <span class="text-xs opacity-60 nums-fa">{{ \App\Support\Digits::toPersian((string) $category->posts_count) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        <div>
            @if($posts->isEmpty())
                <div class="card grid place-items-center py-20 text-sm text-navy-400">مقاله‌ای یافت نشد.</div>
            @else
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach($posts as $post)
                        <article class="card card-hover flex h-full flex-col overflow-hidden">
                            <a href="{{ route('blog.show', $post) }}"
                               class="block aspect-[16/9] bg-gradient-to-br from-navy-100 to-navy-50"></a>
                            <div class="flex flex-1 flex-col p-5">
                                @if($post->category)
                                    <span class="badge w-fit bg-electric-50 text-electric-700">{{ $post->category->name }}</span>
                                @endif
                                <h2 class="mt-3 line-clamp-2 text-base font-bold leading-8 text-navy-900">
                                    <a href="{{ route('blog.show', $post) }}" class="transition hover:text-electric-600">{{ $post->title }}</a>
                                </h2>
                                <p class="mt-2 line-clamp-3 text-xs leading-6 text-navy-500">{{ $post->summary(160) }}</p>
                                <div class="mt-auto flex items-center gap-3 pt-4 text-[11px] text-navy-400 nums-fa">
                                    <span>{{ \App\Support\Digits::toPersian((string) $post->reading_minutes) }} دقیقه مطالعه</span>
                                    <span>·</span>
                                    <span>{{ \App\Support\Digits::toPersian($post->published_at?->format('Y/m/d') ?? '') }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">{{ $posts->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
