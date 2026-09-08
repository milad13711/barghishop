// ثبت سرویس‌ورکر و مدیریت اشتراک پوش نوتیفیکیشن.
// فقط وقتی اجرا می‌شود که کاربر (مشتری یا مدیر) وارد شده باشد و مرورگر
// از Push API پشتیبانی کند — در غیر این صورت بی‌صدا خارج می‌شود.

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

async function subscribeToPush() {
    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!vapidKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js');

        let permission = Notification.permission;

        if (permission === 'default') {
            permission = await Notification.requestPermission();
        }

        if (permission !== 'granted') return;

        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });
        }

        await fetch('/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify(subscription.toJSON()),
        });
    } catch (e) {
        console.warn('[push] subscribe failed', e);
    }
}

// دکمه‌های دارای data-enable-push روی صفحه، این تابع را صدا می‌زنند
window.enablePushNotifications = subscribeToPush;

// اگر کاربر قبلاً اجازه داده، بی‌صدا و خودکار دوباره مشترک می‌شویم
// (مثلاً بعد از پاک‌شدن اشتراک قدیمی سمت سرور)
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
        subscribeToPush();
    }
});
