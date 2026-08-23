<?php

namespace App\Models;

use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasSlug;

    protected $guarded = [];

    public $timestamps = true;

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
