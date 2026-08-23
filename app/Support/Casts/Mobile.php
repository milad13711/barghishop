<?php

namespace App\Support\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * شماره موبایل را همیشه به شکل 09xxxxxxxxx نرمال می‌کند.
 * ورودی‌های +989…، 00989…، 989…، ۰۹… (ارقام فارسی) پشتیبانی می‌شوند.
 */
class Mobile implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return static::normalize($value);
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        return (bool) preg_match('/^09\d{9}$/', (string) static::normalize($value));
    }
}
