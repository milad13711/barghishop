<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

/**
 * تولید خودکار slug یکتا. برای متن فارسی، Str::slug خالی برمی‌گرداند،
 * پس در آن حالت از خود متن فارسی با خط تیره استفاده می‌کنیم (URL فارسی معتبر است).
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
        $base = Str::slug((string) $source);

        if ($base === '') {
            $base = trim(preg_replace('/\s+/u', '-', trim((string) $source)), '-');
            $base = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $base) ?: 'item';
        }

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
