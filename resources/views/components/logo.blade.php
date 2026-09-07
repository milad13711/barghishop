@props(['variant' => 'dark', 'class' => 'h-9'])

@php
    // نشان فشرده (بولت داخل نشان مربعی) — همان طرح فاوآیکون، برای خوانایی
    // در اندازه‌های کوچک هدر/پنل. تصویر کامل و پرجزئیات فقط برای زمینه‌های
    // بزرگ (مثلاً og:image) در public/images/logo.svg نگه داشته شده است.
    $badge = $variant === 'light' ? 'rgba(255,255,255,0.12)' : '#1e3a66';
@endphp

<span {{ $attributes->merge(['class' => $class.' aspect-square shrink-0 rounded-xl grid place-items-center']) }}
      style="background:{{ $badge }}">
    <svg viewBox="0 0 64 64" class="size-[62%]" role="img" aria-label="برقی‌شاپ">
        <title>برقی‌شاپ</title>
        <path d="M40 10 18 36h11l-5 18 22-28H35z" fill="#ffc615"/>
    </svg>
</span>
