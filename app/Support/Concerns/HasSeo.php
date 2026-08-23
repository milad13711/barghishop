<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

trait HasSeo
{
    public function seoTitle(): string
    {
        return $this->seo_title
            ?: trim(($this->name ?? $this->title ?? '').' | '.config('shop.name'));
    }

    public function seoDescription(): string
    {
        $fallback = $this->short_description ?? $this->excerpt ?? $this->description ?? '';

        return Str::limit(strip_tags($this->seo_description ?: $fallback), 160, '');
    }
}
