<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', $seo['title'] ?? config('shop.name'))</title>
    <meta name="description" content="@yield('description', $seo['description'] ?? '')">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">

    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('shop.name') }}">
    <meta property="og:title" content="@yield('title', $seo['title'] ?? config('shop.name'))">
    <meta property="og:description" content="@yield('description', $seo['description'] ?? '')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="fa_IR">
    @if(! empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#1e3a66">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/favicon.svg">
    <meta property="og:image" content="{{ url('/images/logo.svg') }}">

    <link rel="alternate" hreflang="fa-IR" href="{{ url()->current() }}">

    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">

    @include('partials.topbar')
    @include('partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.mobile-nav')

</body>
</html>
