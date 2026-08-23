<?php

namespace Database\Seeders;

use App\Models\LoyaltyLevel;
use Illuminate\Database\Seeder;

class LoyaltyLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'برنزی', 'slug' => 'bronze', 'color' => '#B08D57', 'min_points' => 0,    'discount_percent' => 0, 'points_multiplier' => 1,
             'benefits' => ['عضویت در باشگاه مشتریان', 'اطلاع از تخفیف‌های ویژه']],
            ['name' => 'نقره‌ای', 'slug' => 'silver', 'color' => '#9AA5B1', 'min_points' => 100,  'discount_percent' => 2, 'points_multiplier' => 1.2,
             'benefits' => ['۲٪ تخفیف دائمی', 'ارسال رایگان از ۳ میلیون تومان', 'پشتیبانی اولویت‌دار']],
            ['name' => 'طلایی', 'slug' => 'gold',   'color' => '#C9A227', 'min_points' => 400,  'discount_percent' => 4, 'points_multiplier' => 1.5,
             'benefits' => ['۴٪ تخفیف دائمی', 'ارسال رایگان نامحدود', 'گارانتی تعویض ۷ روزه', 'کارشناس اختصاصی']],
            ['name' => 'پلاتینیوم', 'slug' => 'platinum', 'color' => '#0A84FF', 'min_points' => 1200, 'discount_percent' => 6, 'points_multiplier' => 2,
             'benefits' => ['۶٪ تخفیف دائمی', 'ارسال رایگان نامحدود', 'اولویت تأمین کالا', 'خدمات نصب با تخفیف']],
        ];

        foreach ($levels as $i => $level) {
            LoyaltyLevel::updateOrCreate(['slug' => $level['slug']], $level + ['sort' => $i + 1]);
        }
    }
}
