<?php

namespace App\Support;

/**
 * همه مبالغ در دیتابیس «ریال» و عدد صحیح‌اند.
 * این کلاس تنها مرجع تبدیل و نمایش مبلغ است.
 */
final class Money
{
    public static function toToman(int $rial): int
    {
        return intdiv($rial, 10);
    }

    public static function fromToman(int|string $toman): int
    {
        return (int) $toman * 10;
    }

    /** مثال: ۲٬۴۵۰٬۰۰۰ تومان */
    public static function format(?int $rial, bool $withUnit = true): string
    {
        if ($rial === null) {
            return '—';
        }

        $value = config('shop.currency.display') === 'IRT'
            ? self::toToman($rial)
            : $rial;

        $formatted = Digits::toPersian(number_format($value, 0, '.', '٬'));

        if (! $withUnit) {
            return $formatted;
        }

        return $formatted.' '.(config('shop.currency.display') === 'IRT' ? 'تومان' : 'ریال');
    }

    public static function round(int $rial): int
    {
        $step = (int) config('shop.currency.round_to', 1);

        return $step > 1 ? (int) (round($rial / $step) * $step) : $rial;
    }
}
