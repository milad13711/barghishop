<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * تبدیل و قالب‌بندی تاریخ شمسی — بدون هیچ وابستگی بیرونی.
 *
 * الگوریتم تبدیل، پیاده‌سازی استاندارد و رایج تقویم هجری شمسی است.
 * همه تاریخ‌های نمایشی سایت از اینجا عبور می‌کنند؛ تاریخ میلادی فقط در
 * خروجی‌های ماشینی (JSON-LD و sitemap) باقی می‌ماند که استاندارد ISO 8601
 * الزامشان می‌کند و کاربر آن‌ها را نمی‌بیند.
 */
final class Jalali
{
    public const MONTHS = [
        1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    public const WEEKDAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

    /** میلادی → شمسی. خروجی: [سال، ماه، روز] */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $monthDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy2  = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
              + intdiv($gy2 + 399, 400) + $gd + $monthDays[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /** شمسی → میلادی. خروجی: [سال، ماه، روز] */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;

        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4)
              + $jd + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $days--;
            $gy += 100 * intdiv($days, 36524);
            $days %= 36524;

            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;

        $isLeap = ($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0;
        $lengths = [31, $isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        $gm = 1;

        while ($gm <= 12 && $gd > $lengths[$gm - 1]) {
            $gd -= $lengths[$gm - 1];
            $gm++;
        }

        return [$gy, $gm, $gd];
    }

    public static function isLeapYear(int $jy): bool
    {
        return self::daysInMonth($jy, 12) === 30;
    }

    public static function daysInMonth(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        // اسفند: با تبدیل ۱ فروردین سال بعد و برگشت یک روز تعیین می‌شود
        [$gy, $gm, $gd] = self::toGregorian($jy + 1, 1, 1);
        $prev = CarbonImmutable::create($gy, $gm, $gd)->subDay();
        [, , $jd] = self::toJalali($prev->year, $prev->month, $prev->day);

        return $jd;
    }

    /**
     * قالب‌بندی تاریخ شمسی.
     *
     * نشانه‌ها: Y سال | y سال دورقمی | m ماه دورقمی | n ماه | d روز دورقمی |
     *           j روز | F نام ماه | l نام روز هفته | H ساعت | i دقیقه | s ثانیه
     */
    public static function format(
        DateTimeInterface|string|null $date,
        string $format = 'Y/m/d',
        bool $persianDigits = true,
    ): string {
        if (blank($date)) {
            return '—';
        }

        $date = $date instanceof DateTimeInterface
            ? CarbonImmutable::instance($date)
            : CarbonImmutable::parse($date);

        $date = $date->setTimezone(config('app.timezone'));

        [$jy, $jm, $jd] = self::toJalali($date->year, $date->month, $date->day);

        $replacements = [
            'Y' => (string) $jy,
            'y' => substr((string) $jy, -2),
            'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
            'n' => (string) $jm,
            'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
            'j' => (string) $jd,
            'F' => self::MONTHS[$jm],
            'l' => self::WEEKDAYS[($date->dayOfWeek + 1) % 7],
            'H' => $date->format('H'),
            'i' => $date->format('i'),
            's' => $date->format('s'),
        ];

        $out = '';

        foreach (str_split($format) as $char) {
            $out .= $replacements[$char] ?? $char;
        }

        return $persianDigits ? Digits::toPersian($out) : $out;
    }

    /** «۱۱ شهریور ۱۴۰۵» */
    public static function long(DateTimeInterface|string|null $date): string
    {
        return self::format($date, 'j F Y');
    }

    /** ورودی کاربر «۱۴۰۵/۰۶/۱۱» یا «1405-6-11» → Carbon میلادی */
    public static function parse(?string $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        $value = Digits::toEnglish(trim($value));

        if (! preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', $value, $m)) {
            return null;
        }

        [, $jy, $jm, $jd] = array_map('intval', $m);

        if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
            return null;
        }

        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);

        return CarbonImmutable::create($gy, $gm, $gd, 0, 0, 0, config('app.timezone'));
    }

    /** سال جاری شمسی — برای کپی‌رایت فوتر و مانند آن */
    public static function currentYear(): int
    {
        $now = CarbonImmutable::now(config('app.timezone'));

        return self::toJalali($now->year, $now->month, $now->day)[0];
    }
}
