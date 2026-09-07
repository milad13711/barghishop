<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProvider;
use App\Contracts\SmsResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** درایور توسعه: پیامک واقعی ارسال نمی‌کند، فقط لاگ می‌کند. */
class LogSmsProvider implements SmsProvider
{
    protected function cacheKey(string $mobile): string
    {
        return "otp:log:$mobile";
    }
    public function code(): string
    {
        return 'log';
    }

    public function send(string $mobile, string $text): SmsResult
    {
        Log::channel('single')->info("[SMS] $mobile: $text");

        return SmsResult::success('log-'.uniqid());
    }

    public function sendPattern(string $mobile, string $patternCode, array $params): SmsResult
    {
        Log::channel('single')->info("[SMS:$patternCode] $mobile: ".json_encode($params, JSON_UNESCAPED_UNICODE));

        return SmsResult::success('log-'.uniqid());
    }

    public function sendCode(string $mobile, string $footer = ''): SmsResult
    {
        // هم‌طول با کد واقعی سرویس لیمو (۶ رقم) تا محیط توسعه با تولید یکسان باشد.
        $length = (int) config('shop.otp.length', 6);
        $code = (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);
        Cache::put($this->cacheKey($mobile), $code, now()->addMinutes(2));

        Log::channel('single')->info("[OTP] $mobile: $code");

        return SmsResult::success('log-'.uniqid());
    }

    public function checkCode(string $mobile, string $code): SmsResult
    {
        $expected = Cache::get($this->cacheKey($mobile));

        if ($expected === null) {
            return SmsResult::failure('کد تایید منقضی شده است');
        }

        if (! hash_equals($expected, $code)) {
            return SmsResult::failure('کدتایید نادرست می باشد');
        }

        Cache::forget($this->cacheKey($mobile));

        return SmsResult::success();
    }

    public function credit(): ?int
    {
        return null;
    }
}
