<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const PENDING_PAYMENT = 'pending_payment';
    public const PAID            = 'paid';
    public const PROCESSING      = 'processing';
    public const SHIPPED         = 'shipped';
    public const DELIVERED       = 'delivered';
    public const CANCELLED       = 'cancelled';
    public const REFUNDED        = 'refunded';

    /** گذارهای مجاز وضعیت — تنها مرجع، در OrderStatusService استفاده می‌شود. */
    public const TRANSITIONS = [
        self::PENDING_PAYMENT => [self::PAID, self::PROCESSING, self::CANCELLED],
        self::PAID            => [self::PROCESSING, self::CANCELLED, self::REFUNDED],
        self::PROCESSING      => [self::SHIPPED, self::CANCELLED, self::REFUNDED],
        self::SHIPPED         => [self::DELIVERED, self::REFUNDED],
        self::DELIVERED       => [self::REFUNDED],
        self::CANCELLED       => [],
        self::REFUNDED        => [],
    ];

    protected $guarded = [];

    protected $casts = [
        'address_snapshot' => 'array',
        'paid_at'          => 'datetime',
        'shipped_at'       => 'datetime',
        'delivered_at'     => 'datetime',
        'subtotal'         => 'integer',
        'discount_total'   => 'integer',
        'coupon_discount'  => 'integer',
        'loyalty_discount' => 'integer',
        'wallet_used'      => 'integer',
        'shipping_cost'    => 'integer',
        'cod_fee'          => 'integer',
        'tax_total'        => 'integer',
        'grand_total'      => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class, 'price_tier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /** شماره سفارش خوانا: BS-140501-0042 */
    public static function generateCode(): string
    {
        return 'BS-'.now()->format('ymd').'-'.str_pad((string) (static::max('id') + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
