<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * ⚠️ داده نمونه برای دیدن ظاهر سایت.
 * نام مدل‌ها، مشخصات فنی و قیمت‌ها «تقریبی و آزمایشی» هستند و باید پیش از
 * راه‌اندازی واقعی با کاتالوگ رسمی سیماران و لیست قیمت روز جایگزین شوند.
 * مسیر جایگزینی: پنل مدیریت ← محصولات ← ایمپورت اکسل/CSV
 *
 * همه مبالغ به ریال هستند.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $simaran = Brand::updateOrCreate(['slug' => 'simaran'], [
            'name'        => 'سیماران',
            'description' => 'سیماران، تولیدکننده ایرانی آیفون تصویری، سیستم‌های کنترل تردد و تجهیزات هوشمندسازی ساختمان. برقی‌شاپ نماینده رسمی فروش محصولات سیماران است.',
            'is_active'   => true,
            'sort'        => 1,
            'seo_title'   => 'خرید محصولات سیماران با قیمت نمایندگی | برقی‌شاپ',
            'seo_description' => 'قیمت روز آیفون تصویری سیماران، پنل، حافظه‌دار و لوازم جانبی با گارانتی اصالت و ارسال سریع به سراسر ایران.',
        ]);

        $videoIntercom = $this->category('آیفون تصویری', 'video-intercom', null, [
            'description' => 'انواع آیفون تصویری سیماران با نمایشگر ۴.۳ تا ۱۰ اینچ، مدل‌های حافظه‌دار، لمسی و وای‌فای‌دار.',
            'seo_title'   => 'قیمت و خرید آیفون تصویری سیماران | برقی‌شاپ',
            'sort'        => 1,
        ]);

        $memory  = $this->category('آیفون تصویری حافظه‌دار', 'video-intercom-memory', $videoIntercom->id, ['sort' => 1]);
        $touch   = $this->category('آیفون تصویری لمسی', 'video-intercom-touch', $videoIntercom->id, ['sort' => 2]);
        $classic = $this->category('آیفون تصویری کلاسیک', 'video-intercom-classic', $videoIntercom->id, ['sort' => 3]);

        $panels = $this->category('پنل و درب‌بازکن', 'panels', null, [
            'description' => 'پنل‌های کدینگ، تک‌واحدی و چندواحدی سیماران به همراه قفل برقی و درب‌بازکن.',
            'sort'        => 2,
        ]);

        $audio = $this->category('آیفون صوتی', 'audio-intercom', null, [
            'description' => 'آیفون‌های صوتی سیماران برای ساختمان‌های مسکونی و اداری.',
            'sort'        => 3,
        ]);

        $accessories = $this->category('لوازم جانبی', 'accessories', null, [
            'description' => 'منبع تغذیه، توزیع‌کننده، قفل برقی و سایر متعلقات نصب.',
            'sort'        => 4,
        ]);

        // [sku, نام, دسته, قیمت خرده, وزن گرم, گارانتی ماه, ویژه؟, توضیح کوتاه, مشخصات]
        $products = [
            ['SIM-HS-43TK', 'آیفون تصویری سیماران مدل HS-43TK', $classic, 98_000_000, 1400, 24, false,
             'مانیتور ۴.۳ اینچ رنگی با بدنه جمع‌وجور، مناسب واحدهای مسکونی.',
             ['اندازه نمایشگر' => '۴.۳ اینچ', 'نوع نمایشگر' => 'TFT رنگی', 'حافظه تصویر' => 'ندارد', 'نوع گوشی' => 'با گوشی', 'تعداد پاسخگویی' => '۱ واحد']],

            ['SIM-HS-43TKM', 'آیفون تصویری حافظه‌دار سیماران مدل HS-43TKM', $memory, 128_000_000, 1500, 24, true,
             'مدل حافظه‌دار ۴.۳ اینچ با قابلیت ذخیره تصویر مراجعین در زمان عدم حضور.',
             ['اندازه نمایشگر' => '۴.۳ اینچ', 'حافظه تصویر' => 'دارد', 'ظرفیت حافظه' => 'داخلی', 'نوع گوشی' => 'با گوشی', 'تغذیه' => '۲۲۰ ولت']],

            ['SIM-HS-72TKM', 'آیفون تصویری حافظه‌دار سیماران مدل HS-72TKM', $memory, 189_000_000, 1900, 24, true,
             'نمایشگر ۷ اینچ حافظه‌دار با کیفیت تصویر بالا و طراحی مدرن.',
             ['اندازه نمایشگر' => '۷ اینچ', 'حافظه تصویر' => 'دارد', 'نوع گوشی' => 'با گوشی', 'قابلیت توسعه' => 'تا ۴ مانیتور', 'تغذیه' => '۲۲۰ ولت']],

            ['SIM-HS-78TKM', 'آیفون تصویری سیماران مدل HS-78TKM بدون گوشی', $touch, 236_000_000, 2000, 24, true,
             'مدل بدون گوشی (هندزفری) با نمایشگر ۷ اینچ و کلیدهای لمسی.',
             ['اندازه نمایشگر' => '۷ اینچ', 'نوع گوشی' => 'بدون گوشی (هندزفری)', 'کلیدها' => 'لمسی', 'حافظه تصویر' => 'دارد']],

            ['SIM-IV-100M', 'آیفون تصویری لمسی سیماران سری آی‌ویژن ۱۰ اینچ', $touch, 412_000_000, 2600, 24, true,
             'نمایشگر ۱۰ اینچ تمام‌لمسی با رابط کاربری گرافیکی و حافظه داخلی.',
             ['اندازه نمایشگر' => '۱۰ اینچ', 'نوع صفحه' => 'تمام لمسی', 'حافظه تصویر' => 'دارد', 'رابط کاربری' => 'گرافیکی فارسی', 'نوع گوشی' => 'بدون گوشی']],

            ['SIM-HS-2S', 'آیفون صوتی سیماران مدل HS-2S', $audio, 32_000_000, 600, 18, false,
             'گوشی صوتی سیماران با کیفیت صدای شفاف و دوام بالا.',
             ['نوع' => 'صوتی', 'کلید درب‌بازکن' => 'دارد', 'کلید سکرت' => 'دارد', 'جنس بدنه' => 'ABS']],

            ['SIM-PNL-1U', 'پنل تک واحدی سیماران', $panels, 74_000_000, 900, 24, false,
             'پنل ورودی تک‌واحدی با دوربین رنگی و بدنه ضدآب.',
             ['تعداد واحد' => '۱', 'دوربین' => 'رنگی', 'زاویه دید' => 'وایداَنگل', 'مقاومت' => 'ضدآب / ضدضربه']],

            ['SIM-PNL-CODE', 'پنل کدینگ سیماران', $panels, 158_000_000, 1200, 24, true,
             'پنل کدینگ مناسب ساختمان‌های چندواحدی با صفحه‌کلید و نمایشگر شماره واحد.',
             ['نوع' => 'کدینگ', 'ظرفیت' => 'چندواحدی', 'صفحه‌کلید' => 'فلزی', 'دوربین' => 'رنگی']],

            ['SIM-LOCK-12V', 'قفل برقی درب سیماران ۱۲ ولت', $accessories, 21_000_000, 700, 12, false,
             'قفل برقی استاندارد ۱۲ ولت، سازگار با تمام مدل‌های آیفون سیماران.',
             ['ولتاژ' => '۱۲ ولت', 'جنس' => 'فلزی', 'کاربرد' => 'درب چوبی و فلزی']],

            ['SIM-PWR-DIST', 'منبع تغذیه و توزیع‌کننده سیماران', $accessories, 45_000_000, 800, 12, false,
             'ماژول تغذیه و توزیع سیگنال برای اتصال چند مانیتور به یک پنل.',
             ['ورودی' => '۲۲۰ ولت', 'خروجی' => 'تغذیه مانیتور و پنل', 'کاربرد' => 'ساختمان چندواحدی']],
        ];

        foreach ($products as $i => [$sku, $name, $category, $retail, $weight, $warranty, $featured, $short, $specs]) {
            $product = Product::updateOrCreate(['sku' => $sku], [
                'brand_id'          => $simaran->id,
                'category_id'       => $category->id,
                'name'              => $name,
                'short_description' => $short,
                'body'              => $this->body($name, $short),
                'status'            => Product::PUBLISHED,
                'is_featured'       => $featured,
                'warranty_months'   => $warranty,
                'weight_grams'      => $weight,
                'stock'             => random_int(4, 40),
                'track_stock'       => true,
                'published_at'      => now()->subDays(30 - $i),
                'seo_title'         => "$name | قیمت روز و خرید از برقی‌شاپ",
                'seo_description'   => mb_substr($short, 0, 150),
            ]);

            $product->specs()->delete();

            foreach (array_values(array_map(null, array_keys($specs), $specs)) as $sort => [$key, $value]) {
                $product->specs()->create([
                    'group'         => 'مشخصات فنی',
                    'key'           => $key,
                    'value'         => $value,
                    'is_filterable' => in_array($key, ['اندازه نمایشگر', 'حافظه تصویر', 'نوع گوشی'], true),
                    'sort'          => $sort,
                ]);
            }

            $this->prices($product, $retail);
        }
    }

    protected function category(string $name, string $slug, ?int $parentId, array $extra = []): Category
    {
        return Category::updateOrCreate(['slug' => $slug], array_merge([
            'name'      => $name,
            'parent_id' => $parentId,
            'is_active' => true,
        ], $extra));
    }

    /** قیمت خرده + سه سطح عمده با پله‌های تعدادی. */
    protected function prices(Product $product, int $retail): void
    {
        $product->prices()->delete();

        $rows = [
            ['retail',      1,  $retail,                        (int) round($retail * 1.08)],
            ['wholesale_1', 1,  (int) round($retail * 0.88),    $retail],
            ['wholesale_1', 5,  (int) round($retail * 0.85),    $retail],
            ['wholesale_2', 1,  (int) round($retail * 0.82),    $retail],
            ['wholesale_2', 10, (int) round($retail * 0.78),    $retail],
            ['project',     20, (int) round($retail * 0.74),    $retail],
        ];

        foreach ($rows as [$tierCode, $minQty, $amount, $compareAt]) {
            $tier = PriceTier::where('code', $tierCode)->first();

            if (! $tier) {
                continue;
            }

            Price::create([
                'priceable_type' => $product->getMorphClass(),
                'priceable_id'   => $product->id,
                'price_tier_id'  => $tier->id,
                'min_qty'        => $minQty,
                'amount'         => $amount,
                'compare_at'     => $tierCode === 'retail' ? $compareAt : $compareAt,
                'is_active'      => true,
            ]);
        }
    }

    protected function body(string $name, string $short): string
    {
        return <<<HTML
        <p>$short</p>
        <h2>معرفی $name</h2>
        <p>این محصول از خانواده تجهیزات سیماران است و برای استفاده در ساختمان‌های مسکونی، اداری و تجاری طراحی شده است. برقی‌شاپ به‌عنوان نماینده فروش سیماران، این کالا را با <strong>گارانتی اصالت و سلامت فیزیکی</strong> و ارسال سریع به سراسر کشور عرضه می‌کند.</p>
        <h3>چرا این مدل؟</h3>
        <ul>
            <li>کیفیت ساخت و دوام بالا، متناسب با شرایط برق ایران</li>
            <li>پشتیبانی و تأمین قطعات یدکی در بلندمدت</li>
            <li>نصب و راه‌اندازی ساده توسط تکنسین‌های برق ساختمان</li>
        </ul>
        <h3>راهنمای خرید</h3>
        <p>پیش از خرید، تعداد واحدها، فاصله پنل تا مانیتور و نوع کابل‌کشی موجود را بررسی کنید. کارشناسان برقی‌شاپ برای انتخاب مدل مناسب در کنار شما هستند.</p>
        HTML;
    }
}
