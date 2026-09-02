@props(['variant' => 'dark', 'class' => 'h-9'])

@php
    // روی پس‌زمینه روشن: بدنه سرمه‌ای | روی پس‌زمینه تیره: بدنه سفید
    $body = $variant === 'light' ? '#ffffff' : '#1e3a66';
    $gap  = $variant === 'light' ? '#16294a' : '#ffffff';
@endphp

<svg viewBox="0 0 140 104" class="{{ $class }} w-auto" role="img" aria-label="برقی‌شاپ"
     {{ $attributes }}>
    <title>برقی‌شاپ</title>

    <g fill="#ffc615">
        <path d="M6 44 42 40v8z"/>
        <path d="M0 58 40 54v8z"/>
        <path d="M12 72 42 68.5v7z"/>
    </g>

    <path d="M30 22h16l9 17" fill="none" stroke="{{ $body }}" stroke-width="10"
          stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M30 22h9" fill="none" stroke="#ffc615" stroke-width="10" stroke-linecap="round"/>

    <path d="M48 30h58a8 8 0 0 1 8 8v22a8 8 0 0 1-8 8H63z" fill="{{ $body }}"/>

    <path d="M102 36h3a13.5 13.5 0 0 1 0 27h-3z" fill="#ffc615" stroke="{{ $gap }}"
          stroke-width="4" stroke-linejoin="round"/>
    <g fill="#ffc615">
        <path d="M119 41h11a3.5 3.5 0 0 1 0 7h-11z"/>
        <path d="M119 55h11a3.5 3.5 0 0 1 0 7h-11z"/>
    </g>

    <path d="M88 31 57 59h15l-6 13 29-26H80z" fill="#ffc615" stroke="{{ $gap }}"
          stroke-width="4" stroke-linejoin="round"/>

    <rect x="52" y="74" width="64" height="9" rx="4.5" fill="{{ $body }}"/>

    <circle cx="70" cy="93" r="8.5" fill="{{ $body }}"/>
    <circle cx="70" cy="93" r="3.8" fill="#ffc615"/>
    <circle cx="102" cy="93" r="8.5" fill="{{ $body }}"/>
    <circle cx="102" cy="93" r="3.8" fill="#ffc615"/>
</svg>
