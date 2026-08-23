<?php

namespace App\Models;

use App\Support\Concerns\HasSeo;
use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasSlug, HasSeo;

    protected $guarded = [];

    protected $casts = [
        'is_active'            => 'boolean',
        'show_in_menu'         => 'boolean',
        'prices_require_login' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    /** شناسه این دسته و همه زیردسته‌ها — برای فیلتر لیست محصولات. */
    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /** مسیر آبشاری برای breadcrumb. */
    public function breadcrumb(): array
    {
        $trail = [$this];
        $node = $this;

        while ($node = $node->parent) {
            array_unshift($trail, $node);
        }

        return $trail;
    }
}
