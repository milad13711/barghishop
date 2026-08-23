<?php

namespace App\Support;

final class Digits
{
    private const EN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    private const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    public static function toPersian(string $value): string
    {
        return str_replace(self::EN, self::FA, $value);
    }

    public static function toEnglish(string $value): string
    {
        return str_replace(
            array_merge(self::FA, ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']),
            array_merge(self::EN, self::EN),
            $value
        );
    }

    /** نرمال‌سازی متن فارسی برای جستجو: ي→ی، ك→ک، حذف اعراب و نیم‌فاصله. */
    public static function normalizeSearch(string $value): string
    {
        $value = self::toEnglish($value);

        $value = strtr($value, [
            'ي' => 'ی', 'ك' => 'ک', 'ۀ' => 'ه', 'ة' => 'ه', 'أ' => 'ا',
            'إ' => 'ا', 'آ' => 'ا', 'ؤ' => 'و', 'ئ' => 'ی', "\u{200C}" => ' ',
        ]);

        $value = preg_replace('/[\x{064B}-\x{0652}]/u', '', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
