<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;

/**
 * محاسبه هزینه ارسال — ترکیبی:
 *   flat   → نرخ ثابت روی خود روش ارسال
 *   weight → نرخ استانی + وزن (base_cost تا base_weight_grams، سپس per_kg_cost)
 *   pickup → رایگان (تحویل حضوری)
 *
 * سقف ارسال رایگان سه‌لایه است: نرخ استان ← روش ارسال ← تنظیم سراسری.
 */
class ShippingCalculator
{
    /** @return \Illuminate\Support\Collection<int, ShippingQuote> */
    public function quotes(int $totalWeightGrams, int $orderSubtotal, ?int $provinceId): \Illuminate\Support\Collection
    {
        return ShippingMethod::active()
            ->with('rates')
            ->get()
            ->map(fn (ShippingMethod $m) => $this->quote($m, $totalWeightGrams, $orderSubtotal, $provinceId))
            ->filter(fn (ShippingQuote $q) => $q->isAvailable())
            ->values();
    }

    public function quote(
        ShippingMethod $method,
        int $totalWeightGrams,
        int $orderSubtotal,
        ?int $provinceId,
    ): ShippingQuote {
        if (! $method->servesProvince($provinceId)) {
            return new ShippingQuote($method, 0, false, 0, 0,
                'این روش ارسال در استان انتخابی سرویس‌دهی نمی‌کند.');
        }

        if ($method->pricing_mode === ShippingMethod::MODE_PICKUP) {
            return new ShippingQuote($method, 0, true, 0, 0);
        }

        if ($method->max_weight_grams && $totalWeightGrams > $method->max_weight_grams) {
            return new ShippingQuote($method, 0, false, 0, 0,
                'وزن سفارش بیش از حد مجاز این روش ارسال است.');
        }

        $rate = $method->pricing_mode === ShippingMethod::MODE_WEIGHT
            ? $method->rateFor($provinceId)
            : null;

        if ($method->pricing_mode === ShippingMethod::MODE_WEIGHT && ! $rate) {
            return new ShippingQuote($method, 0, false, 0, 0,
                'ارسال به این استان با این روش امکان‌پذیر نیست.');
        }

        $freeOver = $rate?->free_over
            ?? $method->free_over
            ?? (int) config('shop.shipping.free_over');

        $minDays = $method->min_days + ($rate->extra_days ?? 0);
        $maxDays = $method->max_days + ($rate->extra_days ?? 0);

        if ($freeOver > 0 && $orderSubtotal >= $freeOver) {
            return new ShippingQuote($method, 0, true, $minDays, $maxDays);
        }

        $cost = $rate
            ? $this->weightCost($rate->base_cost, $rate->base_weight_grams, $rate->per_kg_cost, $totalWeightGrams)
            : (int) $method->flat_cost;

        return new ShippingQuote($method, $cost, false, $minDays, $maxDays);
    }

    protected function weightCost(int $baseCost, int $baseWeight, int $perKg, int $weight): int
    {
        if ($weight <= $baseWeight || $perKg === 0) {
            return $baseCost;
        }

        $extraKg = (int) ceil(($weight - $baseWeight) / 1000);

        return $baseCost + ($extraKg * $perKg);
    }
}
