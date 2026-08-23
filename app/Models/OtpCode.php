<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < config('shop.otp.max_attempts');
    }
}
