<?php

namespace App\Models;

use App\Support\Casts\Mobile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsLog extends Model
{
    protected $guarded = [];

    protected $casts = ['mobile' => Mobile::class];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
