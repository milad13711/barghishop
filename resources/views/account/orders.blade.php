@extends('account.layout')

@section('account')
<div class="card p-6">
    <h1 class="mb-5 text-sm font-bold text-navy-900">سفارش‌های من</h1>
    @include('account.partials.order-table', ['orders' => $orders])
    @if($orders->hasPages())<div class="mt-6">{{ $orders->links() }}</div>@endif
</div>
@endsection
