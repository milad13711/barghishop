@props(['items' => []])

<nav aria-label="مسیر" class="mb-5 flex flex-wrap items-center gap-1.5 text-xs text-navy-400">
    <a href="{{ route('home') }}" class="transition hover:text-electric-600">خانه</a>
    @foreach($items as $label => $url)
        <span class="text-navy-200">/</span>
        @if($url && ! $loop->last)
            <a href="{{ $url }}" class="transition hover:text-electric-600">{{ $label }}</a>
        @else
            <span class="font-medium text-navy-600">{{ $label }}</span>
        @endif
    @endforeach
</nav>

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect(['خانه' => route('home')])->merge($items)
        ->values()
        ->map(fn ($url, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => collect(['خانه' => route('home')])->merge($items)->keys()[$i],
            'item' => $url,
        ])->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
