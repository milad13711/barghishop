<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@barghishop.com'],
            ['name' => 'مدیر فروشگاه', 'password' => 'password']
        );

        $this->call([
            PriceTierSeeder::class,
            LoyaltyLevelSeeder::class,
            ProvinceSeeder::class,
            ShippingSeeder::class,
            SettingSeeder::class,
            CatalogSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
