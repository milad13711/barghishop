<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

/**
 * مدل ترکیبی ارسال:
 *  - پیک تهران: نرخ ثابت
 *  - پست پیشتاز و تیپاکس: بر اساس استان (zone) و وزن
 *  - تحویل حضوری: رایگان
 *
 * مبالغ به ریال هستند.
 */
class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $tehranZone = Province::whereIn('slug', ['tehran', 'alborz'])->pluck('id')->all();

        $courier = ShippingMethod::updateOrCreate(['code' => 'tehran_courier'], [
            'name'         => 'پیک موتوری تهران',
            'description'  => 'تحویل همان روز در محدوده شهر تهران',
            'pricing_mode' => ShippingMethod::MODE_FLAT,
            'flat_cost'    => 850_000,          // ۸۵ هزار تومان
            'free_over'    => 30_000_000,       // بالای ۳ میلیون تومان رایگان
            'max_weight_grams' => 20_000,
            'allowed_province_ids' => $tehranZone,
            'min_days'     => 0,
            'max_days'     => 1,
            'is_active'    => true,
            'sort'         => 1,
        ]);

        $post = ShippingMethod::updateOrCreate(['code' => 'post_pishtaz'], [
            'name'         => 'پست پیشتاز',
            'description'  => 'ارسال به سراسر کشور با کد رهگیری',
            'pricing_mode' => ShippingMethod::MODE_WEIGHT,
            'free_over'    => 50_000_000,       // بالای ۵ میلیون تومان رایگان
            'max_weight_grams' => 30_000,
            'cod_enabled'  => false,
            'min_days'     => 2,
            'max_days'     => 4,
            'is_active'    => true,
            'sort'         => 2,
        ]);

        $tipax = ShippingMethod::updateOrCreate(['code' => 'tipax'], [
            'name'         => 'تیپاکس (پس‌کرایه)',
            'description'  => 'مناسب سفارش‌های حجیم و عمده؛ هزینه در مقصد پرداخت می‌شود',
            'pricing_mode' => ShippingMethod::MODE_WEIGHT,
            'free_over'    => null,
            'cod_enabled'  => true,
            'cod_fee'      => 0,
            'min_days'     => 2,
            'max_days'     => 5,
            'is_active'    => true,
            'sort'         => 3,
        ]);

        ShippingMethod::updateOrCreate(['code' => 'pickup'], [
            'name'         => 'تحویل حضوری از فروشگاه',
            'description'  => 'دریافت رایگان از انبار مرکزی، پس از هماهنگی تلفنی',
            'pricing_mode' => ShippingMethod::MODE_PICKUP,
            'min_days'     => 0,
            'max_days'     => 1,
            'is_active'    => true,
            'sort'         => 4,
        ]);

        // نرخ پایه (province_id = null) — پوشش همه استان‌هایی که نرخ اختصاصی ندارند
        $this->rate($post,  null, 900_000,  1000, 250_000, 0);
        $this->rate($tipax, null, 1_200_000, 1000, 300_000, 0);

        // نرخ اختصاصی بر اساس منطقه
        foreach (Province::all() as $province) {
            [$postBase, $postPerKg, $extraDays] = match ($province->zone) {
                1       => [650_000,  200_000, 0],
                2       => [900_000,  250_000, 0],
                default => [1_250_000, 350_000, 2],
            };

            $this->rate($post, $province->id, $postBase, 1000, $postPerKg, $extraDays);
            $this->rate($tipax, $province->id, (int) ($postBase * 1.3), 1000, (int) ($postPerKg * 1.2), $extraDays);
        }
    }

    protected function rate(ShippingMethod $method, ?int $provinceId, int $base, int $baseWeight, int $perKg, int $extraDays): void
    {
        ShippingRate::updateOrCreate(
            ['shipping_method_id' => $method->id, 'province_id' => $provinceId],
            [
                'base_cost'         => $base,
                'base_weight_grams' => $baseWeight,
                'per_kg_cost'       => $perKg,
                'extra_days'        => $extraDays,
                'is_active'         => true,
            ]
        );
    }
}
