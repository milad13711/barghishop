@extends('admin.layouts.app')
@section('heading', 'مشتریان')

@section('content')
<div class="space-y-5">
    @if($pendingCount)
        <div class="rounded-xl bg-gold-50 px-4 py-3 text-sm text-gold-800 nums-fa">
            {{ \App\Support\Digits::toPersian((string) $pendingCount) }} درخواست همکاری در انتظار بررسی است.
        </div>
    @endif

    <div class="card p-4">
        <form method="get" class="flex flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ request('q') }}" class="input max-w-xs !py-2.5" placeholder="نام، موبایل یا نام فروشگاه">
            <select name="status" class="input max-w-[200px] !py-2.5">
                <option value="">همه مشتریان</option>
                <option value="pending" @selected(request('status')==='pending')>در انتظار تأیید همکاری</option>
                <option value="approved" @selected(request('status')==='approved')>همکاران تأییدشده</option>
                <option value="rejected" @selected(request('status')==='rejected')>ردشده</option>
            </select>
            <button type="submit" class="btn-primary !py-2.5 !text-xs">اعمال</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($customers as $customer)
            <div class="card p-5">
                <form action="{{ route('admin.customers.update', $customer) }}" method="post"
                      class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    @csrf @method('PATCH')

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <div class="text-sm font-bold text-navy-900">{{ $customer->name ?: 'بدون نام' }}</div>
                            <div class="mt-1 text-xs text-navy-400 nums-fa" dir="ltr">{{ $customer->mobile }}</div>
                            @if($customer->company)
                                <div class="mt-1 text-xs text-navy-500">{{ $customer->company }}</div>
                            @endif
                        </div>

                        <div class="text-xs text-navy-500">
                            <div class="nums-fa">{{ \App\Support\Digits::toPersian((string) $customer->orders_count) }} سفارش</div>
                            <div class="mt-1 nums-fa">{{ \App\Support\Digits::toPersian((string) $customer->points) }} امتیاز</div>
                            <div class="mt-1">{{ $customer->loyaltyLevel?->name ?: '—' }}</div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold text-navy-600">سطح قیمت</label>
                            <select name="price_tier_id" class="input !py-2 !text-xs">
                                @foreach($tiers as $tier)
                                    <option value="{{ $tier->id }}" @selected($customer->price_tier_id === $tier->id)>{{ $tier->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold text-navy-600">وضعیت همکاری</label>
                            <select name="wholesale_status" class="input !py-2 !text-xs">
                                @foreach(['none' => 'عادی', 'pending' => 'در انتظار بررسی', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'] as $value => $label)
                                    <option value="{{ $value }}" @selected($customer->wholesale_status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-navy-600">
                            <input type="checkbox" name="is_active" value="1" @checked($customer->is_active)
                                   class="size-4 rounded border-navy-200 text-electric-500 focus:ring-electric-500">
                            فعال
                        </label>
                        <button type="submit" class="btn-primary !py-2 !text-xs">ذخیره</button>
                    </div>
                </form>

                @if($customer->wholesale_note)
                    <p class="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-6 text-navy-600">
                        <span class="font-semibold">توضیح درخواست:</span> {{ $customer->wholesale_note }}
                    </p>
                @endif
            </div>
        @empty
            <div class="card p-12 text-center text-navy-400">مشتری‌ای یافت نشد.</div>
        @endforelse
    </div>

    @if($customers->hasPages())<div>{{ $customers->links() }}</div>@endif
</div>
@endsection
