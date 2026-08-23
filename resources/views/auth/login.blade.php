@extends('layouts.app')

@section('content')
<div class="container-app grid place-items-center py-16">
    <div class="card w-full max-w-md p-8">
        <h1 class="text-xl font-extrabold text-navy-900">ورود یا ثبت‌نام</h1>
        <p class="mt-2 text-sm leading-7 text-navy-500">
            شماره موبایل خود را وارد کنید. کد ورود برای شما پیامک می‌شود.
        </p>

        @if(session('success'))
            <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <form action="{{ route('auth.send-code') }}" method="post" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-xs font-semibold text-navy-700">شماره موبایل</label>
                <input type="tel" name="mobile" value="{{ old('mobile') }}" dir="ltr" inputmode="numeric"
                       placeholder="09121234567" class="input text-center tracking-widest" required autofocus>
                @error('mobile')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-primary w-full">دریافت کد ورود</button>
        </form>

        <p class="mt-6 text-center text-[11px] leading-6 text-navy-400">
            با ورود، <a href="{{ route('pages.about') }}" class="text-electric-600">قوانین و مقررات</a> فروشگاه را می‌پذیرید.
        </p>
    </div>
</div>
@endsection
