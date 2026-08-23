<?php

namespace App\Contracts;

/**
 * قرارداد پنل پیامکی. درایور پیش‌فرض: لیمو اس‌ام‌اس (اکسیرپیامک).
 */
interface SmsProvider
{
    public function code(): string;

    /** ارسال پیامک متنی ساده. */
    public function send(string $mobile, string $text): SmsResult;

    /** ارسال با پترن/الگو — برای OTP و پیام‌های تراکنشی الزامی است. */
    public function sendPattern(string $mobile, string $patternCode, array $params): SmsResult;

    /** اعتبار باقی‌مانده پنل (ریال یا تعداد) — برای نمایش در داشبورد ادمین. */
    public function credit(): ?int;
}
