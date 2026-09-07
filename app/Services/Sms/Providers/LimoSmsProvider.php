<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProvider;
use App\Contracts\SmsResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * لیمو اس‌ام‌اس (اکسیرپیامک) — https://api.limosms.com
 *
 * فیلدهای send() از مستندات واقعی «متد ارسال پیام» در پنل کاربری تأیید شده‌اند
 * (https://api.limosms.com/api/sendsms، پارامترهای SenderNumber/Message/MobileNumber،
 * پاسخ Success/Message/MessageId). هنگام تست با کلید واقعی، اگر SenderNumber خالی
 * رد شود و سرویس خطا داد، شماره خط اختصاصی را در LIMO_SMS_SENDER ست کنید.
 *
 * sendPattern() و credit() هنوز تأیید نشده‌اند — بخش‌های «ارسال پترن» و «دریافت
 * اعتبار» در پنل با جاوااسکریپت لود می‌شوند و مستقیم قابل واکشی نبودند. تا وقتی
 * SMS_PATTERN_* در .env خالی است، SmsManager خودکار از send() با متن آماده
 * استفاده می‌کند و اصلاً به sendPattern() نمی‌رسد — پس این نقص فعلاً بی‌اثر است.
 */
class LimoSmsProvider implements SmsProvider
{
    public function code(): string
    {
        return 'limo';
    }

    public function send(string $mobile, string $text): SmsResult
    {
        return $this->call('/sendsms', array_filter([
            'SenderNumber' => $this->config('sender'),
            'Message'      => $text,
            'MobileNumber' => [$mobile],
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /** @deprecated فرمت واقعی این متد تأیید نشده — قبل از تنظیم SMS_PATTERN_* در .env حتماً تست شود. */
    public function sendPattern(string $mobile, string $patternCode, array $params): SmsResult
    {
        return $this->call('/sendpatternsms', [
            'Mobile'      => $mobile,
            'PatternCode' => $patternCode,
            'Parameters'  => collect($params)
                ->map(fn ($value, $key) => ['Name' => $key, 'Value' => (string) $value])
                ->values()
                ->all(),
        ]);
    }

    /** آدرس واقعی endpoint اعتبار تأیید نشده؛ عمداً همیشه null برمی‌گرداند تا خطای نادرست نسازد. */
    public function credit(): ?int
    {
        return null;
    }

    protected function call(string $path, array $payload): SmsResult
    {
        try {
            $response = $this->client()->post($this->config('base_url').$path, $payload);
        } catch (\Throwable $e) {
            Log::error('sms.limo.exception', ['path' => $path, 'msg' => $e->getMessage()]);

            return SmsResult::failure('ارتباط با پنل پیامکی برقرار نشد.');
        }

        $json = $response->json() ?? [];
        $ok = $response->successful() && (bool) data_get($json, 'Success', false);

        return $ok
            ? SmsResult::success((string) data_get($json, 'MessageId.0', ''), $json)
            : SmsResult::failure((string) (data_get($json, 'Message') ?: 'ارسال پیامک ناموفق بود.'), $json);
    }

    protected function client()
    {
        return Http::timeout(15)
            ->acceptJson()
            ->withHeaders(array_filter([
                'ApiKey' => $this->config('api_key'),
            ]));
    }

    protected function config(string $key): mixed
    {
        return config("shop.sms.providers.limo.$key");
    }
}
