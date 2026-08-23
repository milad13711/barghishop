<?php

namespace App\Services\Loyalty;

use App\Models\Customer;
use App\Models\LoyaltyLevel;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * باشگاه مشتریان: کسب امتیاز، مصرف امتیاز، ارتقای سطح.
 * امتیاز فقط پس از رسیدن سفارش به وضعیت تعیین‌شده در config اعطا می‌شود.
 */
class LoyaltyService
{
    public function awardForOrder(Order $order): void
    {
        if (! config('shop.loyalty.enabled') || ! $order->customer) {
            return;
        }

        // جلوگیری از اعطای دوباره
        if ($order->points_earned > 0) {
            return;
        }

        $points = $this->pointsFor($order);

        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $points) {
            $order->update(['points_earned' => $points]);

            $this->addPoints(
                $order->customer,
                $points,
                'earn',
                'خرید سفارش '.$order->code,
                $order,
            );
        });
    }

    public function pointsFor(Order $order): int
    {
        $per = (int) config('shop.loyalty.rial_per_point');

        if ($per <= 0) {
            return 0;
        }

        $multiplier = $order->customer?->loyaltyLevel?->points_multiplier ?? 1;

        return (int) floor(($order->subtotal / $per) * $multiplier);
    }

    public function addPoints(Customer $customer, int $points, string $type, string $reason, $source = null): void
    {
        DB::transaction(function () use ($customer, $points, $type, $reason, $source) {
            $customer->increment('points', $points);

            $customer->loyaltyEntries()->create([
                'points'      => $points,
                'type'        => $type,
                'reason'      => $reason,
                'source_type' => $source?->getMorphClass(),
                'source_id'   => $source?->getKey(),
            ]);

            $this->syncLevel($customer->refresh());
        });
    }

    /** حداکثر تخفیف ریالی که مشتری می‌تواند با امتیاز روی این مبلغ بگیرد. */
    public function maxRedeemable(Customer $customer, int $subtotal): int
    {
        $byPoints = $customer->points * (int) config('shop.loyalty.point_value_rial');
        $byCap    = (int) floor($subtotal * config('shop.loyalty.max_discount_percent') / 100);

        return max(0, min($byPoints, $byCap));
    }

    public function redeem(Customer $customer, int $rialAmount, Order $order): int
    {
        $value = (int) config('shop.loyalty.point_value_rial');
        $points = (int) ceil($rialAmount / $value);
        $points = min($points, $customer->points);

        if ($points <= 0) {
            return 0;
        }

        $this->addPoints($customer, -$points, 'spend', 'استفاده در سفارش '.$order->code, $order);

        return $points * $value;
    }

    public function syncLevel(Customer $customer): void
    {
        $level = LoyaltyLevel::forPoints($customer->points);

        if ($level && $customer->loyalty_level_id !== $level->id) {
            $customer->update(['loyalty_level_id' => $level->id]);
        }
    }
}
