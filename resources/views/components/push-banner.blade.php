@props(['title' => 'اعلان‌های سفارش را فعال کنید', 'text' => 'از تغییر وضعیت سفارش روی همین دستگاه باخبر شوید.'])

<div x-data="{
        show: false,
        loading: false,
        init() {
            this.show = ('Notification' in window) && ('serviceWorker' in navigator) && Notification.permission === 'default';
        },
        async enable() {
            this.loading = true;
            await window.enablePushNotifications();
            this.loading = false;
            this.show = (Notification.permission === 'default');
        },
     }"
     x-show="show" x-cloak x-transition
     class="card flex flex-wrap items-center justify-between gap-4 bg-electric-50 p-5">
    <div class="flex items-center gap-3">
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-electric-500 text-white">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
        </span>
        <div>
            <div class="text-sm font-bold text-navy-900">{{ $title }}</div>
            <div class="mt-0.5 text-xs text-navy-500">{{ $text }}</div>
        </div>
    </div>
    <button type="button" @click="enable()" :disabled="loading" class="btn-primary shrink-0 !py-2.5 !text-xs">
        <span x-show="!loading">فعال‌سازی</span>
        <span x-show="loading" x-cloak>در حال فعال‌سازی…</span>
    </button>
</div>
