<?php

namespace App\Models;

use App\Support\Concerns\HasSeo;
use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasSlug, HasSeo, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'faq'          => 'array',
        'published_at' => 'datetime',
    ];

    protected static function slugSourceColumn(): string
    {
        return 'title';
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            $words = str_word_count(strip_tags((string) $post->body))
                ?: mb_strlen(strip_tags((string) $post->body)) / 5;
            $post->reading_minutes = max(1, (int) ceil($words / 200));
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function summary(int $length = 160): string
    {
        return Str::limit(strip_tags($this->excerpt ?: $this->body), $length);
    }
}
