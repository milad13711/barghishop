<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProvider;
use App\Contracts\SmsResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * لیمو اس‌ام‌اس (اکسیرپیامک) — https://api.limosms.com
 *
 * فیلدهای send() و sendCode()/checkCode() از مستندات واقعی پنل تأیید شده‌اند
 * و هرکدام با یک ارسال واقعی به شماره تست کنترل شدند:
 *   - /sendsms، /sendcode، /checkcode: SenderNumber/Message/MobileNumber و Mobile/Code
 *   - پاسخ واقعی سرور همیشه camelCase است (success/message)، نه PascalCase
 *     مستندات (Success/Message) — با تست واقعی کشف شد، هر دو حالت پشتیبانی می‌شود.
 *
 * نکته مهم درباره OTP: پیامک عمومی حاوی کد تأیید از طریق /sendsms با کلید
 * تست واقعاً «ارسال‌شده» گزارش شد (messageId معتبر) ولی هرگز به گوشی نرسید —
 * اپراتور محتوای شبیه کد تأیید را از خط اشتراکی فیلتر می‌کند. مسیر اختصاصی
 * /sendcode و /checkcode تنها راهی است که تحویل واقعی تأیید شد؛ به همین دلیل
 * OTP هرگز نباید از send()/sendPattern() عبور کند.
 *
 * sendPattern() و credit() هنوز تأیید نشده‌اند — بخش‌های «ارسال پترن» و «دریافت
 * اعتبار» در پنل با جاوااسکریپت لود می‌شوند و مستقیم قابل واکشی نبودند.
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

    public function sendCode(string $mobile, string $footer = ''): SmsResult
    {
        return $this->call('/sendcode', array_filter([
            'Mobile' => $mobile,
            'Footer' => $footer,
        ], fn ($v) => $v !== ''));
    }

    public function checkCode(string $mobile, string $code): SmsResult
    {
        return $this->call('/checkcode', [
            'Mobile' => $mobile,
            'Code'   => $code,
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

        // پاسخ واقعی سرور فیلدها را حروف کوچک برمی‌گرداند (success/messageId)، نه
        // PascalCase مستندات (Success/MessageId) — با تست پیامک واقعی کشف شد.
        // هر دو حالت را چک می‌کنیم تا اگر روزی endpoint دیگری فرق داشت نشکند.
        $ok = $response->successful()
            && (bool) (data_get($json, 'success') ?? data_get($json, 'Success', false));

        $messageId = data_get($json, 'messageId.0') ?? data_get($json, 'MessageId.0');
        $message = data_get($json, 'message') ?? data_get($json, 'Message');

        return $ok
            ? SmsResult::success((string) ($messageId ?? ''), $json)
            : SmsResult::failure((string) ($message ?: 'ارسال پیامک ناموفق بود.'), $json);
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
