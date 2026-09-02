@extends('admin.layouts.app')
@section('heading', 'سفارش‌ها')

@section('content')
<div class="space-y-5">
    <div class="card p-4">
        <form method="get" class="flex flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ request('q') }}" class="input max-w-xs !py-2.5"
                   placeholder="شماره سفارش، نام یا موبایل مشتری">
            <select name="status" class="input max-w-[200px] !py-2.5">
                <option value="">همه وضعیت‌ها</option>
                @foreach([
                    'pending_payment' => 'در انتظار پرداخت', 'paid' => 'پرداخت‌شده',
                    'processing' => 'در حال آماده‌سازی', 'shipped' => 'ارسال‌شده',
                    'delivered' => 'تحویل‌شده', 'cancelled' => 'لغو شده', 'refunded' => 'مرجوع شده',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>
                        {{ $label }} ({{ \App\Support\Digits::toPersian((string) ($counts[$value] ?? 0)) }})
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary !py-2.5 !text-xs">اعمال</button>
            <a href="{{ route('admin.orders.index') }}" class="btn-ghost !py-2.5 !text-xs">حذف فیلتر</a>
        </form>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead>
                <tr class="border-b border-navy-100 text-right text-xs text-navy-400">
                    <th class="p-4 font-medium">شماره</th>
                    <th class="p-4 font-medium">مشتری</th>
                    <th class="p-4 font-medium">تاریخ</th>
                    <th class="p-4 font-medium">مبلغ</th>
                    <th class="p-4 font-medium">وضعیت</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-navy-50">
                @forelse($orders as $order)
                    <tr class="transition hover:bg-slate-50">
                        <td class="p-4 font-medium text-navy-900 nums-fa" dir="ltr">{{ $order->code }}</td>
                        <td class="p-4">
                            <div class="text-navy-800">{{ $order->customer?->name ?: '—' }}</div>
                            <div class="mt-0.5 text-xs text-navy-400 nums-fa" dir="ltr">{{ $order->customer?->mobile }}</div>
                        </td>
                        <td class="p-4 text-navy-500 nums-fa">@jdt($order->created_at)</td>
                        <td class="p-4 font-bold text-navy-900 nums-fa">{{ \App\Support\Money::format($order->grand_total, false) }}</td>
                        <td class="p-4"><x-admin.status-badge :status="$order->status" /></td>
                        <td class="p-4 text-left">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-semibold text-electric-600">مدیریت</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-12 text-center text-navy-400">سفارشی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())<div>{{ $orders->links() }}</div>@endif
</div>
@endsection
