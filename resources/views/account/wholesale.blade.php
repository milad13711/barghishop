@extends('account.layout')

@section('account')
<div class="card p-6 lg:p-8">
    <h1 class="text-sm font-bold text-navy-900">درخواست همکاری و قیمت عمده</h1>
    <p class="mt-2 text-xs leading-7 text-navy-500">
        اطلاعات صنفی خود را تکمیل کنید. پس از بررسی و تأیید کارشناسان،
        قیمت‌های عمده به‌صورت خودکار در سراسر فروشگاه برای حساب شما نمایش داده می‌شود.
    </p>

    <form action="{{ route('account.wholesale.store') }}" method="post" class="mt-6 grid gap-4 sm:grid-cols-2">
        @csrf
        <div>
            <label class="mb-2 block text-xs font-semibold text-navy-700">نام و نام خانوادگی</label>
            <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="input" required>
            @error('name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-2 block text-xs font-semibold text-navy-700">نام فروشگاه / شرکت</label>
            <input type="text" name="company" value="{{ old('company', $customer->company) }}" class="input" required>
            @error('company')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-2 block text-xs font-semibold text-navy-700">کد ملی</label>
            <input type="text" name="national_id" value="{{ old('national_id', $customer->national_id) }}" dir="ltr" class="input">
        </div>
        <div>
            <label class="mb-2 block text-xs font-semibold text-navy-700">کد اقتصادی (اختیاری)</label>
            <input type="text" name="economic_code" value="{{ old('economic_code', $customer->economic_code) }}" dir="ltr" class="input">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-2 block text-xs font-semibold text-navy-700">سطح همکاری درخواستی</label>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach($tiers as $tier)
                    <label class="cursor-pointer rounded-xl p-4 ring-1 ring-navy-100 transition hover:bg-slate-50">
                        <input type="radio" name="price_tier_id" value="{{ $tier->id }}" @checked($loop->first)
                               class="size-4 text-electric-500 focus:ring-electric-500">
                        <span class="mr-2 text-sm font-bold text-navy-900">{{ $tier->name }}</span>
                        <span class="mt-2 block text-[11px] text-navy-400 nums-fa">
                            تا {{ \App\Support\Digits::toPersian((string) (int) $tier->fallback_discount_percent) }}٪ تخفیف
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="mb-2 block text-xs font-semibold text-navy-700">توضیحات (حوزه فعالیت، حجم خرید ماهانه و…)</label>
            <textarea name="note" rows="3" class="input">{{ old('note', $customer->wholesale_note) }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary">ثبت درخواست همکاری</button>
        </div>
    </form>
</div>
@endsection
