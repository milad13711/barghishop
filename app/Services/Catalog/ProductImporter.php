<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use App\Support\Digits;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * ایمپورت دسته‌ای محصولات از CSV (خروجی اکسل).
 *
 * ستون‌های شناخته‌شده (هدر فارسی یا انگلیسی):
 *   کد کالا | نام | برند | دسته | قیمت خرده | قیمت همکار | قیمت نمایندگی |
 *   موجودی | وزن | گارانتی | وضعیت | توضیح کوتاه | مشخصات
 *
 * ستون «مشخصات» با الگوی «کلید: مقدار | کلید: مقدار» پر می‌شود.
 * همه قیمت‌ها به تومان وارد و به ریال ذخیره می‌شوند.
 * کلید یکتا «کد کالا» است؛ رکورد موجود به‌روزرسانی می‌شود، نه تکراری.
 */
class ProductImporter
{
    public const COLUMNS = [
        'sku'         => ['کد کالا', 'sku', 'کد'],
        'name'        => ['نام', 'نام محصول', 'name', 'title'],
        'brand'       => ['برند', 'brand'],
        'category'    => ['دسته', 'دسته بندی', 'category'],
        'retail'      => ['قیمت خرده', 'قیمت', 'قیمت مصرف کننده', 'price'],
        'wholesale_1' => ['قیمت همکار', 'همکار'],
        'wholesale_2' => ['قیمت نمایندگی', 'نمایندگی'],
        'stock'       => ['موجودی', 'stock'],
        'weight'      => ['وزن', 'weight'],
        'warranty'    => ['گارانتی', 'warranty'],
        'status'      => ['وضعیت', 'status'],
        'short'       => ['توضیح کوتاه', 'توضیحات', 'description'],
        'specs'       => ['مشخصات', 'مشخصات فنی', 'specs'],
    ];

    public function import(string $path): ImportReport
    {
        $report = new ImportReport;

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('فایل قابل خواندن نیست.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return $report;
        }

        // حذف BOM که اکسل ابتدای فایل UTF-8 می‌گذارد
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            // ردیف کاملاً خالی را نادیده بگیر
            if ($values === [null] || implode('', array_map('strval', $values)) === '') {
                continue;
            }

            $row = $this->normalizeRow($this->combine($headers, $values));

            try {
                $this->importRow($row, $report);
            } catch (\Throwable $e) {
                $report->addError($line, $e->getMessage());
            }
        }

        fclose($handle);

        return $report;
    }

    /** ستون‌های کم یا اضافه ردیف را نمی‌شکنند. */
    protected function combine(array $headers, array $values): array
    {
        $values = array_pad(array_slice($values, 0, count($headers)), count($headers), null);

        return array_combine($headers, $values);
    }

    protected function importRow(array $row, ImportReport $report): void
    {
        $sku  = trim((string) ($row['sku'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));

        if ($sku === '' || $name === '') {
            throw new \RuntimeException('«کد کالا» و «نام» الزامی هستند.');
        }

        DB::transaction(function () use ($row, $sku, $name, $report) {
            $exists = Product::withTrashed()->where('sku', $sku)->exists();

            $product = Product::withTrashed()->firstOrNew(['sku' => $sku]);

            $product->fill([
                'name'              => $name,
                'brand_id'          => $this->brandId($row['brand'] ?? null),
                'category_id'       => $this->categoryId($row['category'] ?? null),
                'short_description' => $row['short'] ?? null,
                'stock'             => $this->number($row['stock'] ?? 0),
                'weight_grams'      => $this->number($row['weight'] ?? 0) ?: 1000,
                'warranty_months'   => $this->number($row['warranty'] ?? 0),
                'status'            => $this->status($row['status'] ?? null),
            ]);

            if ($product->status === Product::PUBLISHED && ! $product->published_at) {
                $product->published_at = now();
            }

            $product->save();

            $this->syncSpecs($product, $row['specs'] ?? null);
            $this->syncPrices($product, $row);

            $exists ? $report->updated++ : $report->created++;
        });
    }

    protected function syncSpecs(Product $product, ?string $raw): void
    {
        if (blank($raw)) {
            return;
        }

        $product->specs()->delete();

        foreach (explode('|', $raw) as $sort => $pair) {
            [$key, $value] = array_pad(explode(':', $pair, 2), 2, null);

            if (blank($key) || blank($value)) {
                continue;
            }

            $product->specs()->create([
                'group' => 'مشخصات فنی',
                'key'   => trim($key),
                'value' => trim($value),
                'sort'  => $sort,
            ]);
        }
    }

    protected function syncPrices(Product $product, array $row): void
    {
        foreach (['retail', 'wholesale_1', 'wholesale_2'] as $tierCode) {
            $toman = $this->number($row[$tierCode] ?? 0);

            if ($toman <= 0) {
                continue;
            }

            $tier = PriceTier::where('code', $tierCode)->first();

            if (! $tier) {
                continue;
            }

            Price::updateOrCreate(
                [
                    'priceable_type' => $product->getMorphClass(),
                    'priceable_id'   => $product->id,
                    'price_tier_id'  => $tier->id,
                    'min_qty'        => 1,
                ],
                ['amount' => Money::fromToman($toman), 'is_active' => true],
            );
        }
    }

    protected function brandId(?string $name): ?int
    {
        return blank($name) ? null
            : Brand::firstOrCreate(['name' => trim($name)], ['is_active' => true])->id;
    }

    protected function categoryId(?string $name): ?int
    {
        return blank($name) ? null
            : Category::firstOrCreate(['name' => trim($name)], ['is_active' => true])->id;
    }

    protected function status(?string $value): string
    {
        return match (trim((string) $value)) {
            'پیش‌نویس', 'پیش نویس', 'draft' => Product::DRAFT,
            'بایگانی', 'archived'           => Product::ARCHIVED,
            default                          => Product::PUBLISHED,
        };
    }

    /** ارقام فارسی، جداکننده هزارگان و فاصله را پاک می‌کند. */
    protected function number(mixed $value): int
    {
        return (int) preg_replace('/\D/', '', Digits::toEnglish((string) $value));
    }

    /** هدرهای فایل را به کلیدهای داخلی نگاشت می‌کند. */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $header => $value) {
            $header = Digits::normalizeSearch(mb_strtolower(trim((string) $header)));

            foreach (self::COLUMNS as $key => $aliases) {
                foreach ($aliases as $alias) {
                    if ($header === Digits::normalizeSearch(mb_strtolower($alias))) {
                        $normalized[$key] = is_string($value) ? trim($value) : $value;
                        break 2;
                    }
                }
            }
        }

        return $normalized;
    }
}
