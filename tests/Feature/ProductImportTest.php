<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\ProductImporter;
use App\Services\Pricing\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PriceTierSeeder::class);
    }

    protected function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_it_imports_products_with_persian_headers(): void
    {
        $path = $this->csv(<<<CSV
        کد کالا,نام,برند,دسته,قیمت خرده,قیمت همکار,موجودی,وزن,گارانتی,مشخصات
        SIM-IMP-1,آیفون تصویری نمونه,سیماران,آیفون تصویری,"9,800,000",8600000,12,1500,24,اندازه نمایشگر: ۷ اینچ | حافظه تصویر: دارد
        CSV);

        $report = app(ProductImporter::class)->import($path);

        $this->assertSame(1, $report->created);
        $this->assertEmpty($report->errors);

        $product = Product::where('sku', 'SIM-IMP-1')->firstOrFail();

        $this->assertSame('آیفون تصویری نمونه', $product->name);
        $this->assertSame(12, $product->stock);
        $this->assertSame(1500, $product->weight_grams);
        $this->assertSame(24, $product->warranty_months);
        $this->assertSame(Product::PUBLISHED, $product->status);

        // برند و دسته خودکار ساخته می‌شوند
        $this->assertNotNull(Brand::where('name', 'سیماران')->first());
        $this->assertNotNull(Category::where('name', 'آیفون تصویری')->first());

        // مشخصات فنی از الگوی «کلید: مقدار»
        $this->assertSame(2, $product->specs()->count());
        $this->assertSame('۷ اینچ', $product->specs()->where('key', 'اندازه نمایشگر')->value('value'));
    }

    public function test_prices_are_converted_from_toman_to_rial(): void
    {
        $path = $this->csv(<<<CSV
        کد کالا,نام,قیمت خرده,قیمت همکار
        SIM-IMP-2,محصول نمونه,"۹,۸۰۰,۰۰۰",8600000
        CSV);

        app(ProductImporter::class)->import($path);

        $product = Product::where('sku', 'SIM-IMP-2')->firstOrFail();
        $resolver = app(PriceResolver::class);

        $this->assertSame(98_000_000, $resolver->retailFor($product)->amount);

        $wholesaleTier = PriceTier::where('code', 'wholesale_1')->firstOrFail();
        $this->assertSame(86_000_000,
            (int) $product->prices()->where('price_tier_id', $wholesaleTier->id)->value('amount'));
    }

    public function test_reimporting_the_same_sku_updates_instead_of_duplicating(): void
    {
        $first = $this->csv("کد کالا,نام,قیمت خرده,موجودی\nSIM-IMP-3,نام قدیمی,1000000,5");
        $second = $this->csv("کد کالا,نام,قیمت خرده,موجودی\nSIM-IMP-3,نام جدید,2000000,20");

        $importer = app(ProductImporter::class);

        $importer->import($first);
        $report = $importer->import($second);

        $this->assertSame(1, $report->updated);
        $this->assertSame(0, $report->created);
        $this->assertSame(1, Product::where('sku', 'SIM-IMP-3')->count());

        $product = Product::where('sku', 'SIM-IMP-3')->firstOrFail();

        $this->assertSame('نام جدید', $product->name);
        $this->assertSame(20, $product->stock);
        $this->assertSame(20_000_000, app(PriceResolver::class)->retailFor($product)->amount);

        // قیمت جایگزین می‌شود، نه اینکه ردیف دوم اضافه شود
        $this->assertSame(1, $product->prices()->count());
    }

    public function test_rows_missing_required_fields_are_reported_not_fatal(): void
    {
        $path = $this->csv(<<<CSV
        کد کالا,نام,قیمت خرده
        ,بدون کد کالا,1000
        SIM-IMP-4,محصول درست,2000000
        CSV);

        $report = app(ProductImporter::class)->import($path);

        $this->assertSame(1, $report->created);
        $this->assertCount(1, $report->errors);
        $this->assertStringContainsString('الزامی', $report->errors[0]);
        $this->assertNotNull(Product::where('sku', 'SIM-IMP-4')->first());
    }

    public function test_admin_can_upload_a_file_and_download_the_template(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $admin = User::create(['name' => 'ادمین', 'email' => 'a@b.com', 'password' => 'secret']);

        $this->actingAs($admin)->get(route('admin.products.import'))->assertOk();

        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "کد کالا,نام,قیمت خرده\nSIM-IMP-5,محصول آپلودی,3000000",
        );

        $this->actingAs($admin)
            ->post(route('admin.products.import.store'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(Product::where('sku', 'SIM-IMP-5')->first());

        $this->actingAs($admin)
            ->get(route('admin.products.import.template'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
