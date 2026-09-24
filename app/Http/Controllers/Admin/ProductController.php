<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['brand', 'category', 'media'])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%$term%")->orWhere('sku', 'like', "%$term%")))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category_id', $c))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', $this->formData(new Product));
    }

    public function edit(Product $product)
    {
        $product->load(['specs', 'media', 'prices']);

        return view('admin.products.form', $this->formData($product));
    }

    public function store(Request $request)
    {
        $product = DB::transaction(fn () => $this->persist(new Product, $request));

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'محصول ساخته شد. اکنون می‌توانید تصاویر و قیمت‌ها را تکمیل کنید.');
    }

    public function update(Request $request, Product $product)
    {
        DB::transaction(fn () => $this->persist($product, $request));

        return back()->with('success', 'تغییرات ذخیره شد.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'محصول حذف شد.');
    }

    protected function persist(Product $product, Request $request): Product
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:200'],
            'sku'               => ['required', 'string', 'max:60', 'unique:products,sku,'.($product->id ?? 'NULL')],
            'brand_id'          => ['nullable', 'exists:brands,id'],
            'category_id'       => ['nullable', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'body'              => ['nullable', 'string'],
            'status'            => ['required', 'in:draft,published,archived'],
            'warranty_months'   => ['nullable', 'integer', 'min:0', 'max:120'],
            'weight_grams'      => ['nullable', 'integer', 'min:0'],
            'stock'             => ['nullable', 'integer'],
            'seo_title'         => ['nullable', 'string', 'max:200'],
            'seo_description'   => ['nullable', 'string', 'max:500'],
            'specs'             => ['nullable', 'array'],
            'specs.*.key'       => ['nullable', 'string', 'max:120'],
            'specs.*.value'     => ['nullable', 'string', 'max:1000'],
            'prices'            => ['nullable', 'array'],
            'images.*'          => ['nullable', 'image', 'max:4096'],
            'variants'          => ['nullable', 'array', 'max:60'],
            'variants.*.id'     => ['nullable', 'integer'],
            'variants.*.name'   => ['nullable', 'string', 'max:150'],
            'variants.*.sku'    => ['nullable', 'string', 'max:60'],
            'variants.*.options' => ['nullable', 'string', 'max:500'],
            'variants.*.stock'  => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0'],
            'variants.*.images'   => ['nullable', 'array', 'max:12'],
            'variants.*.images.*' => ['nullable', 'image', 'max:4096'],
        ]);

        // specs/prices ستون جدول نیستند و نباید mass-assign شوند
        $product->fill(Arr::except($data, ['specs', 'prices', 'images', 'variants']) + [
            'is_featured'     => $request->boolean('is_featured'),
            'track_stock'     => $request->boolean('track_stock'),
            'allow_backorder' => $request->boolean('allow_backorder'),
            'prices_require_login' => $request->boolean('prices_require_login'),
        ]);

        if ($product->status === Product::PUBLISHED && ! $product->published_at) {
            $product->published_at = now();
        }

        $product->save();

        $this->syncSpecs($product, $request->input('specs', []));
        $this->syncPrices($product, $request->input('prices', []));
        $this->syncVariants($product, $request->input('variants', []), $request);
        $this->storeImages($product, $request);

        return $product;
    }

    protected function syncSpecs(Product $product, array $specs): void
    {
        $product->specs()->delete();

        foreach (array_values($specs) as $sort => $spec) {
            if (blank($spec['key'] ?? null) || blank($spec['value'] ?? null)) {
                continue;
            }

            $product->specs()->create([
                'group'         => $spec['group'] ?? 'مشخصات فنی',
                'key'           => $spec['key'],
                'value'         => $spec['value'],
                'is_filterable' => (bool) ($spec['is_filterable'] ?? false),
                'sort'          => $sort,
            ]);
        }
    }

    /**
     * ورودی فرم به «تومان» است (چون فروشنده با تومان کار می‌کند)
     * ولی در دیتابیس «ریال» ذخیره می‌شود.
     */
    protected function syncPrices(Product $product, array $rows): void
    {
        $product->prices()->delete();

        foreach ($rows as $tierId => $tierRows) {
            if (! PriceTier::whereKey($tierId)->exists()) {
                continue;
            }

            foreach ((array) $tierRows as $row) {
                $amount = (int) preg_replace('/\D/', '', \App\Support\Digits::toEnglish((string) ($row['amount'] ?? '')));

                if ($amount <= 0) {
                    continue;
                }

                $compare = (int) preg_replace('/\D/', '', \App\Support\Digits::toEnglish((string) ($row['compare_at'] ?? '')));

                Price::create([
                    'priceable_type' => $product->getMorphClass(),
                    'priceable_id'   => $product->id,
                    'price_tier_id'  => $tierId,
                    'min_qty'        => max(1, (int) ($row['min_qty'] ?? 1)),
                    'amount'         => Money::fromToman($amount),
                    'compare_at'     => $compare > 0 ? Money::fromToman($compare) : null,
                    'is_active'      => true,
                ]);
            }
        }
    }

    /**
     * مدل‌های محصول (رنگ، تعداد کانال و…). هر مدل موجودی و قیمت مستقل دارد.
     * ستون «ویژگی‌ها» به شکل «رنگ: سفید | کانال: ۴» وارد می‌شود و همان
     * کلیدها در صفحه محصول به انتخاب‌گر تبدیل می‌شوند.
     * ردیف‌های حذف‌شده از فرم، از دیتابیس هم حذف می‌شوند.
     */
    protected function syncVariants(Product $product, array $rows, Request $request): void
    {
        $keep = [];
        $retailTier = PriceTier::retail();

        $i = -1;

        // کلید اصلی ردیف را نگه می‌داریم؛ فایل‌های آپلودی با همان کلید در request هستند
        foreach ($rows as $rowKey => $row) {
            $i++;
            $name = trim((string) ($row['name'] ?? ''));
            $options = $this->parseOptions((string) ($row['options'] ?? ''));

            if ($name === '' && $options === []) {
                continue;
            }

            $name = $name !== '' ? $name : implode('، ', $options);

            $variant = filled($row['id'] ?? null) ? $product->variants()->find($row['id']) : null;
            $variant ??= new ProductVariant(['product_id' => $product->id]);

            $sku = trim((string) ($row['sku'] ?? ''));
            $sku = $sku !== '' ? $sku : ($variant->sku ?: $product->sku.'-'.($i + 1));

            if (ProductVariant::where('sku', $sku)->when($variant->exists, fn ($q) => $q->whereKeyNot($variant->id))->exists()) {
                throw ValidationException::withMessages(['variants' => "کد «{$sku}» برای مدل دیگری استفاده شده است."]);
            }

            $variant->fill([
                'name'         => $name,
                'sku'          => $sku,
                'options'      => $options ?: null,
                'stock'        => (int) ($row['stock'] ?? 0),
                'weight_grams' => (int) ($row['weight_grams'] ?? 0),
                'is_active'    => (bool) ($row['is_active'] ?? false),
            ])->save();

            $this->syncVariantPrices($variant, (array) ($row['prices'] ?? []), $retailTier, $name);
            $this->storeVariantImages($variant, (array) $request->file("variants.$rowKey.images", []));

            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep)->each(function (ProductVariant $gone) {
            $gone->prices()->delete();
            $gone->media()->delete();
            $gone->delete();
        });

        // موجودی کل محصول = مجموع مدل‌های فعال؛ فهرست‌ها و فیلتر «موجود» به آن تکیه دارند
        if ($keep !== []) {
            $product->update([
                'stock'       => (int) $product->variants()->where('is_active', true)->sum('stock'),
                'track_stock' => true,
            ]);
        }
    }

    /** تصاویر مخصوص یک مدل؛ اولین تصویر مدل، تصویر اصلی آن مدل می‌شود. */
    protected function storeVariantImages(ProductVariant $variant, array $files): void
    {
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $count = $variant->media()->count();

            $variant->media()->create([
                'product_id' => $variant->product_id,
                'path'       => $file->store("products/{$variant->product_id}/variants/{$variant->id}", 'public'),
                'alt'        => $variant->product->name.' — '.$variant->label(),
                'is_primary' => $count === 0,
                'sort'       => $count,
            ]);
        }
    }

    protected function syncVariantPrices(ProductVariant $variant, array $prices, PriceTier $retail, string $name): void
    {
        $variant->prices()->delete();

        foreach ($prices as $tierId => $row) {
            $amount = (int) preg_replace('/\D/', '', \App\Support\Digits::toEnglish((string) ($row['amount'] ?? '')));

            if ($amount <= 0 || ! PriceTier::whereKey($tierId)->exists()) {
                continue;
            }

            $compare = (int) preg_replace('/\D/', '', \App\Support\Digits::toEnglish((string) ($row['compare_at'] ?? '')));

            Price::create([
                'priceable_type' => $variant->getMorphClass(),
                'priceable_id'   => $variant->id,
                'price_tier_id'  => $tierId,
                'min_qty'        => 1,
                'amount'         => Money::fromToman($amount),
                'compare_at'     => $compare > 0 ? Money::fromToman($compare) : null,
                'is_active'      => true,
            ]);
        }

        // مدل بدون قیمت خرده نمایش قیمت ندارد؛ بهتر است همین‌جا جلوی ثبت ناقص را بگیریم
        $hasOwnRetail = $variant->prices()->where('price_tier_id', $retail->id)->exists();
        $hasProductRetail = $variant->product->prices()->where('price_tier_id', $retail->id)->exists();

        if (! $hasOwnRetail && ! $hasProductRetail) {
            throw ValidationException::withMessages([
                'variants' => "برای مدل «{$name}» قیمت خرده‌فروشی وارد کنید (یا قیمت پایه محصول را ثبت کنید).",
            ]);
        }
    }

    /** «رنگ: سفید | کانال: ۴» ← ['رنگ' => 'سفید', 'کانال' => '۴'] */
    protected function parseOptions(string $raw): array
    {
        $out = [];

        foreach (explode('|', $raw) as $pair) {
            [$k, $v] = array_pad(explode(':', $pair, 2), 2, null);

            if (filled(trim((string) $k)) && filled(trim((string) $v))) {
                $out[trim($k)] = trim($v);
            }
        }

        return $out;
    }

    protected function storeImages(Product $product, Request $request): void
    {
        foreach ((array) $request->file('images', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('products/'.$product->id, 'public');

            $product->media()->create([
                'path'       => $path,
                'alt'        => $product->name,
                'is_primary' => $product->media()->count() === 0,
                'sort'       => $product->media()->count(),
            ]);
        }
    }

    protected function formData(Product $product): array
    {
        return [
            'product'    => $product,
            'brands'     => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'tiers'      => PriceTier::orderBy('sort')->get(),
            'variantRows' => $this->variantRows($product),
        ];
    }

    /** ردیف‌های اولیه ویرایشگر مدل‌ها برای فرم (قیمت‌ها به تومان). */
    protected function variantRows(Product $product): array
    {
        if (! $product->exists) {
            return [];
        }

        $tiers = PriceTier::orderBy('sort')->get();

        return $product->variants()->with(['prices', 'media'])->orderBy('id')->get()->map(function (ProductVariant $v) use ($tiers) {
            $prices = [];

            foreach ($tiers as $tier) {
                $p = $v->prices->firstWhere('price_tier_id', $tier->id);
                $prices[$tier->id] = [
                    'amount'     => $p ? (string) Money::toToman($p->amount) : '',
                    'compare_at' => $p?->compare_at ? (string) Money::toToman($p->compare_at) : '',
                ];
            }

            return [
                'id'           => $v->id,
                'name'         => $v->name,
                'sku'          => $v->sku,
                'options'      => collect($v->options ?? [])->map(fn ($val, $k) => "$k: $val")->implode(' | '),
                'stock'        => $v->stock,
                'weight_grams' => $v->weight_grams ?: '',
                'is_active'    => $v->is_active,
                'prices'       => $prices,
                'media'        => $v->media->map(fn ($m) => ['id' => $m->id, 'url' => $m->url(), 'is_primary' => $m->is_primary])->all(),
            ];
        })->all();
    }

    public function makePrimaryMedia(Product $product, int $mediaId)
    {
        $media = $product->allMedia()->whereKey($mediaId)->firstOrFail();

        // «تصویر اصلی» فقط در گروه خودش معنا دارد: عمومی محصول یا هر مدل جدا
        $product->allMedia()->where('product_variant_id', $media->product_variant_id)->update(['is_primary' => false]);
        $media->update(['is_primary' => true, 'sort' => -1]);

        return back()->with('success', 'تصویر اصلی تغییر کرد.');
    }

    public function deleteMedia(Product $product, int $mediaId)
    {
        $product->allMedia()->whereKey($mediaId)->delete();

        return back()->with('success', 'تصویر حذف شد.');
    }
}
