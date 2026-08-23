<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

/**
 * تولید خودکار slug یکتا.
 *
 * برای متن فارسی عمداً از Str::slug استفاده نمی‌کنیم، چون حروف فارسی را به
 * لاتین «آوانویسی» می‌کند و نتیجه‌ای مثل «ayfon-tsoyry» می‌سازد که نه برای
 * کاربر خواناست و نه برای گوگل معنایی دارد. در عوض خود متن فارسی را نگه
 * می‌داریم؛ گوگل URLهای یونیکد را کامل پشتیبانی می‌کند و در نتایج جستجو
 * به‌صورت خوانا نمایش می‌دهد.
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (blank($model->slug)) {
                $model->slug = static::makeUniqueSlug(
                    $model->{static::slugSourceColumn()},
                    $model->getKey()
                );
            }
        });
    }

    protected static function slugSourceColumn(): string
    {
        return 'name';
    }

    public static function makeUniqueSlug(?string $source, $ignoreId = null): string
    {
        $base = static::slugify((string) $source);

        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** حروف فارسی و لاتین را نگه می‌دارد، بقیه را به خط تیره تبدیل می‌کند. */
    public static function slugify(string $value): string
    {
        $value = \App\Support\Digits::toEnglish($value);

        // نیم‌فاصله و فاصله به خط تیره
        $value = str_replace(["\u{200C}", "\u{200F}", "\u{200E}"], ' ', $value);
        $value = mb_strtolower(trim($value));

        // هر چیزی جز حرف، رقم و خط تیره → خط تیره
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '-', $value) ?? $value;
        $value = trim(preg_replace('/-+/', '-', $value) ?? $value, '-');

        return $value !== '' ? $value : 'item';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
