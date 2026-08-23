@extends('layouts.app')

@push('schema')
    @foreach($schema as $item)
        {!! \App\Support\Seo\Schema::render($item) !!}
    @endforeach
@endpush

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="array_filter([
        'مقالات' => route('blog.index'),
        $post->category?->name => $post->category ? route('blog.index', ['category' => $post->category->slug]) : null,
        $post->title => null,
    ], fn ($v, $k) => filled($k), ARRAY_FILTER_USE_BOTH)" />

    <div class="grid gap-8 lg:grid-cols-[1fr_300px]">
        <article>
            <header>
                @if($post->category)
                    <span class="badge bg-electric-50 text-electric-700">{{ $post->category->name }}</span>
                @endif
                <h1 class="mt-4 text-2xl font-extrabold leading-10 text-navy-900 lg:text-3xl lg:leading-[1.5]">
                    {{ $post->title }}
                </h1>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-navy-400 nums-fa">
                    <span>{{ \App\Support\Digits::toPersian($post->published_at?->format('Y/m/d') ?? '') }}</span>
                    <span>·</span>
                    <span>{{ \App\Support\Digits::toPersian((string) $post->reading_minutes) }} دقیقه مطالعه</span>
                    <span>·</span>
                    <span>{{ \App\Support\Digits::toPersian((string) $post->view_count) }} بازدید</span>
                </div>
            </header>

            <div class="card mt-6 p-6 lg:p-8">
                <div class="prose-fa max-w-none">
                    {!! $post->body !!}
                </div>

                @if($post->faq)
                    <section class="mt-10 border-t border-navy-50 pt-8">
                        <h2 class="mb-5 text-lg font-extrabold text-navy-900">پرسش‌های پرتکرار</h2>
                        <div class="space-y-3">
                            @foreach($post->faq as $item)
                                <details class="group rounded-xl bg-slate-50 p-4">
                                    <summary class="cursor-pointer list-none text-sm font-bold text-navy-900">
                                        {{ $item['q'] }}
                                    </summary>
                                    <p class="mt-3 text-sm leading-7 text-navy-600">{{ $item['a'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            @if($post->products->isNotEmpty())
                <section class="mt-10">
                    <x-section-heading title="محصولات مرتبط با این مقاله" />
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                        @foreach($post->products->take(3) as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                </section>
            @endif
        </article>

        <aside class="space-y-5 lg:sticky lg:top-28 lg:h-fit">
            @if($related->isNotEmpty())
                <div class="card p-5">
                    <h2 class="mb-4 text-sm font-bold text-navy-900">مطالب مرتبط</h2>
                    <ul class="space-y-3">
                        @foreach($related as $item)
                            <li>
                                <a href="{{ route('blog.show', $item) }}"
                                   class="line-clamp-2 text-sm leading-7 text-navy-600 transition hover:text-electric-600">
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card bg-navy-900 p-6 text-center">
                <p class="text-sm font-bold text-white">مشاوره رایگان خرید</p>
                <p class="mt-2 text-xs leading-6 text-navy-300">در انتخاب مدل مناسب تردید دارید؟ با ما تماس بگیرید.</p>
                <a href="{{ route('pages.contact') }}" class="btn-gold mt-4 w-full !py-2.5 !text-xs">تماس با کارشناس</a>
            </div>
        </aside>
    </div>
</div>
@endsection
