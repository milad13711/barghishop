<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProvider;
use App\Contracts\SmsResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * لیمو اس‌ام‌اس (اکسیرپیامک).
 *
 * توجه: آدرس دقیق endpointها از پنل شما گرفته می‌شود و در .env قابل تنظیم است.
 * اگر مسیرها فرق داشت فقط همین کلاس تغییر می‌کند — بقیه برنامه دست نمی‌خورد.
 */
class LimoSmsProvider implements SmsProvider
{
    public function code(): string
    {
        return 'limo';
    }

    public function send(string $mobile, string $text): SmsResult
    {
        return $this->call('/sendsms', [
            'Mobiles'    => [$mobile],
            'Message'    => $text,
            'LineNumber' => $this->config('sender'),
        ]);
    }

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

    public function credit(): ?int
    {
        try {
            $response = $this->client()->get($this->config('base_url').'/credit')->json();

            return (int) (data_get($response, 'Credit') ?? data_get($response, 'credit') ?? 0);
        } catch (\Throwable) {
            return null;
        }
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
        $ok = $response->successful()
            && ! in_array(strtolower((string) data_get($json, 'Status', 'ok')), ['error', 'failed'], true);

        return $ok
            ? SmsResult::success((string) data_get($json, 'MessageId', ''), $json)
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
