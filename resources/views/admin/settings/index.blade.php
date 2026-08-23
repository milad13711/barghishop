@extends('admin.layouts.app')
@section('heading', 'تنظیمات')

@section('content')
@php
    $groupLabels = ['general' => 'اطلاعات عمومی', 'social' => 'شبکه‌های اجتماعی', 'seo' => 'سئو', 'trust' => 'نمادهای اعتماد'];
    $keyLabels = [
        'shop_name' => 'نام فروشگاه', 'shop_slogan' => 'شعار', 'support_phone' => 'تلفن پشتیبانی',
        'support_mobile' => 'موبایل / واتساپ', 'address' => 'نشانی', 'working_hours' => 'ساعت کاری',
        'instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'whatsapp' => 'واتساپ',
        'home_seo_title' => 'عنوان سئوی صفحه اصلی', 'home_seo_description' => 'توضیحات متای صفحه اصلی',
        'enamad_code' => 'کد نماد اعتماد', 'samandehi_code' => 'کد ساماندهی',
    ];
@endphp

<form action="{{ route('admin.settings.update') }}" method="post" class="max-w-3xl space-y-5">
    @csrf

    @foreach($settings as $group => $items)
        <section class="card space-y-4 p-5">
            <h2 class="text-sm font-bold text-navy-900">{{ $groupLabels[$group] ?? $group }}</h2>

            @foreach($items as $setting)
                <div>
                    <label class="mb-2 block text-xs font-semibold text-navy-700">
                        {{ $keyLabels[$setting->key] ?? $setting->key }}
                    </label>
                    @if($setting->type === 'text' || str_contains($setting->key, 'description') || str_contains($setting->key, 'address'))
                        <textarea name="settings[{{ $setting->key }}]" rows="2" class="input !py-2.5">{{ $setting->value }}</textarea>
                    @else
                        <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="input !py-2.5">
                    @endif
                </div>
            @endforeach
        </section>
    @endforeach

    <button type="submit" class="btn-primary">ذخیره تنظیمات</button>
</form>
@endsection
