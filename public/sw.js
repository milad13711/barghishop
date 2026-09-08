// سرویس‌ورکر برقی‌شاپ — فقط برای نصب PWA و دریافت پوش نوتیفیکیشن.
// عمداً هیچ کشی از صفحات انجام نمی‌شود تا قیمت/موجودی همیشه تازه بماند.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let data = { title: 'برقی‌شاپ', body: '', url: '/' };

    try {
        if (event.data) data = { ...data, ...event.data.json() };
    } catch (e) {
        data.body = event.data ? event.data.text() : '';
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon || '/icons/icon-192.png',
            badge: data.badge || '/icons/icon-192.png',
            dir: 'rtl',
            lang: 'fa',
            data: { url: data.url || '/' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url === url && 'focus' in client) return client.focus();
            }
            return self.clients.openWindow(url);
        })
    );
});
