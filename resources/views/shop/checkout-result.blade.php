@extends('layouts.app')

@section('content')
@php $paid = $order->isPaid(); @endphp

<div class="container-app grid place-items-center py-16">
    <div class="card w-full max-w-lg p-8 text-center">
        <span class="mx-auto grid size-16 place-items-center rounded-2xl
                     {{ $paid ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
            <svg class="size-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                @if($paid)
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                @endif
            </svg>
        </span>

        <h1 class="mt-5 text-lg font-extrabold text-navy-900">
            {{ $paid ? 'سفارش شما با موفقیت ثبت شد' : 'سفارش ثبت شد؛ در انتظار پرداخت' }}
        </h1>

        <p class="mt-3 text-sm leading-7 text-navy-500">
            شماره سفارش شما <span class="font-bold text-navy-900 nums-fa" dir="ltr">{{ $order->code }}</span> است.
            {{ $paid ? 'جزئیات و وضعیت ارسال از طریق پیامک و داشبورد قابل پیگیری است.' : 'در صورت کسر وجه، مبلغ تا ۷۲ ساعت به حساب شما بازمی‌گردد.' }}
        </p>

        @if($errors->any())
            <div class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-xs text-rose-700">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <dl class="mt-6 space-y-3 rounded-xl bg-slate-50 p-5 text-sm">
            <div class="flex justify-between">
                <dt class="text-navy-500">مبلغ کل</dt>
                <dd class="font-bold text-navy-900 nums-fa">{{ \App\Support\Money::format($order->grand_total) }}</dd>
            </div>
            @if($ref = $order->transactions->firstWhere('status', 'success')?->ref_id)
                <div class="flex justify-between">
                    <dt class="text-navy-500">شماره پیگیری بانکی</dt>
                    <dd class="font-bold text-navy-900 nums-fa" dir="ltr">{{ $ref }}</dd>
                </div>
            @endif
        </dl>

        <div class="mt-6 flex gap-3">
            <a href="{{ route('account.orders.show', $order) }}" class="btn-primary flex-1">پیگیری سفارش</a>
            <a href="{{ route('shop.index') }}" class="btn-ghost flex-1">ادامه خرید</a>
        </div>
    </div>
</div>
@endsection
