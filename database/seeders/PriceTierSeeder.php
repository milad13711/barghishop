<?php

namespace Database\Seeders;

use App\Models\PriceTier;
use Illuminate\Database\Seeder;

class PriceTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['code' => 'retail',      'name' => 'خرده‌فروشی', 'is_default' => true,  'is_wholesale' => false, 'requires_approval' => false, 'fallback_discount_percent' => 0,  'sort' => 1],
            ['code' => 'wholesale_1', 'name' => 'همکار',       'is_default' => false, 'is_wholesale' => true,  'requires_approval' => true,  'fallback_discount_percent' => 12, 'sort' => 2],
            ['code' => 'wholesale_2', 'name' => 'نمایندگی',    'is_default' => false, 'is_wholesale' => true,  'requires_approval' => true,  'fallback_discount_percent' => 18, 'sort' => 3],
            ['code' => 'project',     'name' => 'پروژه‌ای',     'is_default' => false, 'is_wholesale' => true,  'requires_approval' => true,  'fallback_discount_percent' => 22, 'sort' => 4],
        ];

        foreach ($tiers as $tier) {
            PriceTier::updateOrCreate(['code' => $tier['code']], $tier);
        }
    }
}
