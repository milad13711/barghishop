@extends('layouts.app')

@section('content')
<div class="container-app grid place-items-center py-16">
    <div class="card w-full max-w-md p-8">
        <h1 class="text-xl font-extrabold text-navy-900">کد ورود را وارد کنید</h1>
        <p class="mt-2 text-sm leading-7 text-navy-500 nums-fa">
            کد پنج‌رقمی ارسال‌شده به شماره
            <span class="font-bold text-navy-900" dir="ltr">{{ \App\Support\Digits::toPersian($mobile) }}</span>
            را وارد کنید.
        </p>

        <form action="{{ route('auth.verify') }}" method="post" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <div>
                <input type="text" name="code" dir="ltr" inputmode="numeric" maxlength="5"
                       class="input text-center text-2xl font-bold tracking-[0.6em]" required autofocus>
                @error('code')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-primary w-full">ورود به حساب</button>
        </form>

        <a href="{{ route('auth.login') }}" class="mt-5 block text-center text-xs font-semibold text-electric-600">
            تغییر شماره موبایل
        </a>
    </div>
</div>
@endsection
