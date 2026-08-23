@props(['status'])
@php
    $map = [
        'pending_payment' => ['در انتظار پرداخت', 'bg-amber-100 text-amber-800'],
        'paid'            => ['پرداخت‌شده', 'bg-emerald-100 text-emerald-800'],
        'processing'      => ['در حال آماده‌سازی', 'bg-electric-100 text-electric-800'],
        'shipped'         => ['ارسال‌شده', 'bg-indigo-100 text-indigo-800'],
        'delivered'       => ['تحویل‌شده', 'bg-emerald-100 text-emerald-800'],
        'cancelled'       => ['لغو شده', 'bg-slate-200 text-slate-700'],
        'refunded'        => ['مرجوع شده', 'bg-rose-100 text-rose-800'],
        'draft'           => ['پیش‌نویس', 'bg-slate-200 text-slate-700'],
        'published'       => ['منتشرشده', 'bg-emerald-100 text-emerald-800'],
        'archived'        => ['بایگانی', 'bg-slate-200 text-slate-700'],
        'none'            => ['—', 'bg-slate-100 text-slate-500'],
        'pending'         => ['در انتظار بررسی', 'bg-amber-100 text-amber-800'],
        'approved'        => ['تأییدشده', 'bg-emerald-100 text-emerald-800'],
        'rejected'        => ['ردشده', 'bg-rose-100 text-rose-800'],
    ];
    [$label, $classes] = $map[$status] ?? [$status, 'bg-slate-100 text-slate-600'];
@endphp
<span class="badge {{ $classes }}">{{ $label }}</span>
