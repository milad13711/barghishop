<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>ورود به پنل مدیریت — {{ config('shop.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-navy-900 p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl">
        <div class="flex items-center gap-2.5">
            <x-logo class="h-10" />
            <div class="leading-tight">
                <div class="text-base font-extrabold text-navy-900">برقی‌شاپ</div>
                <div class="text-[11px] text-navy-400">پنل مدیریت</div>
            </div>
        </div>

        @if($errors->any())
            <div class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-xs text-rose-700">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form action="{{ route('admin.login.store') }}" method="post" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">ایمیل</label>
                <input type="email" name="email" value="{{ old('email') }}" dir="ltr" class="input" required autofocus>
            </div>
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">رمز عبور</label>
                <input type="password" name="password" dir="ltr" class="input" required>
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-xs text-navy-600">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-navy-200 text-electric-500 focus:ring-electric-500">
                مرا به خاطر بسپار
            </label>
            <button type="submit" class="btn-primary w-full">ورود</button>
        </form>
    </div>
</body>
</html>
