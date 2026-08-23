<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const PENDING  = 'pending';
    public const SUCCESS  = 'success';
    public const FAILED   = 'failed';
    public const REFUNDED = 'refunded';

    protected $guarded = [];

    protected $casts = [
        'raw'         => 'array',
        'amount'      => 'integer',
        'verified_at' => 'datetime',
    ];

    protected $hidden = ['raw'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
