@props(['title', 'subtitle' => null, 'href' => null, 'linkLabel' => 'مشاهده همه'])

<div class="mb-6 flex items-end justify-between gap-4">
    <div>
        <h2 class="flex items-center gap-2.5 text-xl font-extrabold text-navy-900 lg:text-2xl">
            <span class="h-6 w-1.5 rounded-full bg-gold-500"></span>
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="mt-2 text-sm text-navy-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if($href)
        <a href="{{ $href }}" class="shrink-0 text-sm font-semibold text-electric-600 transition hover:text-electric-700">
            {{ $linkLabel }} ←
        </a>
    @endif
</div>
