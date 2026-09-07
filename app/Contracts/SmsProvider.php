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

    /** ارسال با پترن/الگو — برای پیام‌های تراکنشی غیر از ورود (تأیید سفارش و مانند آن). */
    public function sendPattern(string $mobile, string $patternCode, array $params): SmsResult;

    /**
     * ارسال کد ورود یکبارمصرف. برخلاف send()/sendPattern()، خودِ کد را درایور
     * می‌سازد و نگه می‌دارد — این برنامه کد را نمی‌بیند و ذخیره نمی‌کند.
     * دلیل: پیامک‌های عمومی حاوی کد عددی از خط اشتراکی توسط اپراتور فیلتر
     * می‌شوند؛ فقط مسیر اختصاصی احراز هویت تحویل تضمین‌شده دارد.
     */
    public function sendCode(string $mobile, string $footer = ''): SmsResult;

    /** بررسی کدی که کاربر وارد کرده، در سمت درایور (نه در دیتابیس ما). */
    public function checkCode(string $mobile, string $code): SmsResult;

    /** اعتبار باقی‌مانده پنل (ریال یا تعداد) — برای نمایش در داشبورد ادمین. */
    public function credit(): ?int;
}
