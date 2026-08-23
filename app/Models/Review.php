<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rating'             => 'integer',
        'verified_purchase'  => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    public function displayName(): string
    {
        return $this->author_name ?: ($this->customer?->name ?: 'کاربر برقی‌شاپ');
    }
}
