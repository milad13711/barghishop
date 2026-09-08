<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * ارسال پوش نوتیفیکیشن با استاندارد Web Push (VAPID) — بدون Firebase یا
 * هیچ سرویس واسط. مرورگرهای خودِ کاربر (کروم، اج، فایرفاکس، سافاری ۱۶+)
 * این استاندارد را پشتیبانی می‌کنند.
 */
class WebPushService
{
    protected ?WebPush $client = null;

    protected function client(): WebPush
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = new WebPush([
            'VAPID' => [
                'subject'    => config('shop.push.subject'),
                'publicKey'  => config('shop.push.public_key'),
                'privateKey' => config('shop.push.private_key'),
            ],
        ]);
    }

    public function isConfigured(): bool
    {
        return filled(config('shop.push.public_key')) && filled(config('shop.push.private_key'));
    }

    /** ارسال به یک مشترک خاص (مدل با ریلیشن pushSubscriptions). */
    public function notify(Model $subscriber, string $title, string $body, ?string $url = null): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url ?? '/',
            'icon'  => '/icons/icon-192.png',
            'badge' => '/icons/icon-192.png',
        ], JSON_UNESCAPED_UNICODE);

        $sent = 0;

        foreach ($subscriber->pushSubscriptions()->get() as $sub) {
            $this->client()->queueNotification(
                Subscription::create([
                    'endpoint'  => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]),
                $payload,
            );
            $sent++;
        }

        if ($sent === 0) {
            return 0;
        }

        foreach ($this->client()->flush() as $report) {
            $endpoint = $report->getEndpoint();

            if (! $report->isSuccess()) {
                Log::warning('push.send.failed', [
                    'endpoint' => $endpoint,
                    'reason'   => $report->getReason(),
                ]);

                // اگر مرورگر می‌گوید اشتراک دیگر معتبر نیست، پاکش می‌کنیم
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $endpoint)->delete();
                }
            }
        }

        $subscriber->pushSubscriptions()->update(['last_used_at' => now()]);

        return $sent;
    }

    /** ارسال به همه مدیران پنل — برای اطلاع سفارش جدید و مانند آن. */
    public function notifyAdmins(string $title, string $body, ?string $url = null): void
    {
        \App\Models\User::has('pushSubscriptions')->get()
            ->each(fn ($admin) => $this->notify($admin, $title, $body, $url));
    }
}
