<?php

namespace App\Models;

use App\Support\Casts\Mobile;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable implements AuthenticatableContract
{
    use SoftDeletes;

    public const WHOLESALE_NONE     = 'none';
    public const WHOLESALE_PENDING  = 'pending';
    public const WHOLESALE_APPROVED = 'approved';
    public const WHOLESALE_REJECTED = 'rejected';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'mobile'                 => Mobile::class,
        'is_active'              => 'boolean',
        'accepts_sms'            => 'boolean',
        'password'               => 'hashed',
        'mobile_verified_at'     => 'datetime',
        'wholesale_requested_at' => 'datetime',
        'wallet_balance'         => 'integer',
        'points'                 => 'integer',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class, 'price_tier_id');
    }

    public function loyaltyLevel(): BelongsTo
    {
        return $this->belongsTo(LoyaltyLevel::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyEntries(): HasMany
    {
        return $this->hasMany(LoyaltyLedgerEntry::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    public function defaultAddress(): ?Address
    {
        return $this->addresses()->orderByDesc('is_default')->orderByDesc('id')->first();
    }

    /** سطح قیمت مؤثر: اگر عمده تأیید نشده باشد، خرده‌فروشی. */
    public function effectiveTier(): PriceTier
    {
        if ($this->tier
            && $this->tier->is_wholesale
            && $this->wholesale_status !== self::WHOLESALE_APPROVED
        ) {
            return PriceTier::retail();
        }

        return $this->tier ?? PriceTier::retail();
    }

    public function isWholesaler(): bool
    {
        return $this->wholesale_status === self::WHOLESALE_APPROVED;
    }
}
