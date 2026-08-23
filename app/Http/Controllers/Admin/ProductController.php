<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        ]);

        // specs/prices ستون جدول نیستند و نباید mass-assign شوند
        $product->fill(Arr::except($data, ['specs', 'prices', 'images']) + [
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
        ];
    }

    public function deleteMedia(Product $product, int $mediaId)
    {
        $product->media()->whereKey($mediaId)->delete();

        return back()->with('success', 'تصویر حذف شد.');
    }
}
