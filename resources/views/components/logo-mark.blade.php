@props(['class' => 'size-10'])

{{-- نشان مربعی — برای جاهای تنگ مثل نوار کناری پنل --}}
<span {{ $attributes->merge(['class' => 'grid place-items-center rounded-xl bg-navy-800 '.$class]) }}>
    <svg viewBox="0 0 64 64" class="size-[62%]" aria-hidden="true">
        <path d="M40 10 18 36h11l-5 18 22-28H35z" fill="#ffc615"/>
    </svg>
</span>
