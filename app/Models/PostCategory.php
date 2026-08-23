<?php

namespace App\Models;

use App\Support\Concerns\HasSeo;
use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostCategory extends Model
{
    use HasSlug, HasSeo;

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
