<?php

namespace App\Services\Sms\Providers;

use App\Contracts\SmsProvider;
use App\Contracts\SmsResult;
use Illuminate\Support\Facades\Log;

/** درایور توسعه: پیامک واقعی ارسال نمی‌کند، فقط لاگ می‌کند. */
class LogSmsProvider implements SmsProvider
{
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

    public function credit(): ?int
    {
        return null;
    }
}
