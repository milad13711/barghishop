<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['general', 'shop_name', 'برقی‌شاپ', 'string'],
            ['general', 'shop_slogan', 'نماینده رسمی فروش سیماران', 'string'],
            ['general', 'support_phone', '021-00000000', 'string'],
            ['general', 'support_mobile', '09000000000', 'string'],
            ['general', 'address', 'تهران، ...', 'string'],
            ['general', 'working_hours', 'شنبه تا چهارشنبه ۹ تا ۱۸ – پنجشنبه ۹ تا ۱۳', 'string'],
            ['social', 'instagram', '', 'string'],
            ['social', 'telegram', '', 'string'],
            ['social', 'whatsapp', '', 'string'],
            ['seo', 'home_seo_title', 'برقی‌شاپ | خرید آیفون تصویری سیماران با قیمت نمایندگی', 'string'],
            ['seo', 'home_seo_description', 'فروش آنلاین آیفون تصویری، پنل و تجهیزات برق ساختمان سیماران با گارانتی اصالت، قیمت عمده برای همکاران و ارسال سریع به سراسر ایران.', 'string'],
            ['trust', 'enamad_code', '', 'text'],
            ['trust', 'samandehi_code', '', 'text'],
        ];

        foreach ($settings as [$group, $key, $value, $type]) {
            Setting::firstOrCreate(['key' => $key], compact('group', 'value', 'type'));
        }
    }
}
