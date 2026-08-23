@extends('layouts.app')

@section('content')
<div class="container-app py-8">
    <x-breadcrumbs :items="['درباره ما' => null]" />

    <div class="card overflow-hidden">
        <div class="bg-navy-900 px-8 py-12 lg:px-12">
            <span class="badge bg-gold-500 text-navy-950">نماینده فروش سیماران</span>
            <h1 class="mt-4 text-2xl font-extrabold text-white lg:text-3xl">درباره برقی‌شاپ</h1>
            <p class="mt-4 max-w-2xl text-sm leading-8 text-navy-200">
                برقی‌شاپ فروشگاه تخصصی آیفون تصویری و تجهیزات برق ساختمان است. ما محصولات سیماران را
                با قیمت نمایندگی به مصرف‌کننده و همکاران صنفی عرضه می‌کنیم و در کنار فروش،
                مشاوره فنی انتخاب، نصب و پشتیبانی پس از فروش ارائه می‌دهیم.
            </p>
        </div>

        <div class="grid gap-8 p-8 md:grid-cols-3 lg:p-12">
            @foreach([
                ['کالای اصل', 'تمام محصولات مستقیماً از کانال رسمی تأمین می‌شوند و دارای گارانتی معتبرند.'],
                ['قیمت شفاف', 'قیمت مصرف‌کننده و قیمت همکار به‌صورت جداگانه و بدون چانه‌زنی مشخص است.'],
                ['پشتیبانی واقعی', 'کارشناسان ما پیش از خرید در انتخاب و پس از خرید در نصب همراه شما هستند.'],
            ] as [$title, $text])
                <div>
                    <h2 class="flex items-center gap-2 text-base font-bold text-navy-900">
                        <span class="h-5 w-1 rounded-full bg-gold-500"></span>{{ $title }}
                    </h2>
                    <p class="mt-3 text-sm leading-8 text-navy-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
