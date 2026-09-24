<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Services\Pricing\PriceResolver;
use App\Support\Digits;
use App\Support\Money;
use App\Support\Seo\Schema;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request, null);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, $category);
    }

    public function brand(Request $request, Brand $brand)
    {
        abort_unless($brand->is_active, 404);

        return $this->listing($request, null, $brand);
    }

    public function search(Request $request)
    {
        return $this->listing($request, null, null, search: true);
    }

    protected function listing(Request $request, ?Category $category, ?Brand $brand = null, bool $search = false)
    {
        $query = Product::published()->with(['brand', 'media', 'prices', 'category', 'variants.prices']);

        if ($category) {
            $query->whereIn('category_id', $category->descendantIds());
        }

        if ($brand) {
            $query->where('brand_id', $brand->id);
        }

        if ($term = trim((string) $request->query('q'))) {
            $normalized = Digits::normalizeSearch($term);

            $query->where(function ($q) use ($normalized) {
                $q->where('name', 'like', "%$normalized%")
                    ->orWhere('sku', 'like', "%$normalized%")
                    ->orWhere('short_description', 'like', "%$normalized%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%$normalized%"));
            });
        }

        // فیلتر برند از نوار کناری
        if ($brandIds = array_filter((array) $request->query('brands'))) {
            $query->whereIn('brand_id', $brandIds);
        }

        // فیلتر بر اساس مشخصات فنی: ?spec[اندازه نمایشگر]=۷ اینچ
        foreach ((array) $request->query('spec', []) as $key => $value) {
            if (blank($value)) {
                continue;
            }

            $query->whereHas('specs', fn ($s) => $s->where('key', $key)->whereIn('value', (array) $value));
        }

        if ($request->boolean('available')) {
            $query->inStock();
        }

        $query = $this->applySort($query, $request->query('sort'));

        $products = $query->paginate(24)->withQueryString();

        $scopeIds = $category ? $category->descendantIds() : null;

        return view('shop.index', [
            'seo' => [
                'title'       => $this->title($category, $brand, $search ? $request->query('q') : null),
                'description' => $category?->seoDescription() ?: ($brand?->seoDescription() ?: ''),
            ],
            'products'   => $products,
            'category'   => $category,
            'brand'      => $brand,
            'searchTerm' => $request->query('q'),
            'brands'     => Brand::active()->orderBy('sort')->get(),
            'facets'     => $this->facets($scopeIds),
            'subCategories' => $category?->children ?? Category::active()->roots()->orderBy('sort')->get(),
        ]);
    }

    protected function applySort($query, ?string $sort)
    {
        return match ($sort) {
            'cheapest'   => $query->orderBy('id'), // مرتب‌سازی قیمتی در فاز بعد با ستون کش قیمت
            'expensive'  => $query->orderByDesc('id'),
            'bestseller' => $query->orderByDesc('sold_count'),
            'popular'    => $query->orderByDesc('view_count'),
            default      => $query->orderByDesc('is_featured')->latest('published_at'),
        };
    }

    /** مقادیر قابل فیلتر مشخصات فنی در محدوده جاری. */
    protected function facets(?array $productScopeIds): array
    {
        return ProductSpec::query()
            ->where('is_filterable', true)
            ->when($productScopeIds, fn ($q) => $q->whereHas('product',
                fn ($p) => $p->whereIn('category_id', $productScopeIds)))
            ->get()
            ->groupBy('key')
            ->map(fn ($specs) => $specs->pluck('value')->unique()->sort()->values()->all())
            ->all();
    }

    protected function title(?Category $category, ?Brand $brand, ?string $term): string
    {
        if ($term) {
            return "جستجوی «{$term}» | ".config('shop.name');
        }

        if ($category) {
            return $category->seoTitle();
        }

        if ($brand) {
            return $brand->seoTitle();
        }

        return 'فروشگاه محصولات | '.config('shop.name');
    }

    /**
     * داده انتخاب‌گر مدل‌ها برای صفحه محصول. قیمت هر مدل همین‌جا برای همین
     * مشتری (خرده/عمده) از PriceResolver می‌آید تا سمت مرورگر هیچ محاسبه قیمتی
     * نداشته باشیم و قیمت عمده هم فقط به همکار تأییدشده برسد.
     */
    protected function variantData(Product $product, PriceResolver $resolver, $customer): array
    {
        $active = $product->activeVariants();

        if ($active->isEmpty()) {
            return ['variants' => [], 'groups' => [], 'default' => null, 'initial' => null];
        }

        $wholesaler = $customer?->isWholesaler();

        $rows = $active->map(function (\App\Models\ProductVariant $v) use ($product, $resolver, $customer, $wholesaler) {
            $price = $resolver->for($v, $customer);
            $retail = $wholesaler ? $resolver->retailFor($v) : null;
            $max = $v->maxOrderable($product);

            return [
                'id'        => $v->id,
                'label'     => $v->label(),
                'options'   => (object) ($v->options ?? []),
                'available' => $v->isAvailable($product),
                'max'       => $max,
                'stockText' => $max !== null && $max > 0 && $max <= 5
                    ? 'تنها '.Digits::toPersian((string) $max).' عدد در انبار'
                    : null,
                'hidden'    => $price->hidden,
                'callFor'   => ! $price->hidden && $price->amount <= 0,
                'price'     => Money::format($price->amount, false),
                'compare'   => $price->hasDiscount() ? Money::format($price->compareAt, false) : null,
                'discount'  => $price->hasDiscount() ? Digits::toPersian((string) $price->discountPercent()) : null,
                'tier'      => $price->tier->is_wholesale ? $price->tier->name : null,
                'retail'    => $retail && $retail->amount > $price->amount ? Money::format($retail->amount, false) : null,
                'tierRows'  => $wholesaler
                    ? $resolver->tiersFor($v, $customer->effectiveTier())
                        ->map(fn ($r) => ['min' => Digits::toPersian((string) $r->min_qty), 'text' => Money::format($r->amount)])->values()->all()
                    : [],
                'rawAmount' => $price->amount,
            ];
        })->values();

        // انتخاب‌گر چندمحوره فقط وقتی معنا دارد که همه مدل‌ها دقیقاً همان کلیدها را داشته باشند
        $keySets = $active->map(fn ($v) => array_keys($v->options ?? []))->unique(fn ($k) => json_encode($k));
        $groups = [];

        if ($keySets->count() === 1 && $keySets->first() !== []) {
            foreach ($keySets->first() as $key) {
                $groups[] = [
                    'key'    => $key,
                    'values' => $active->map(fn ($v) => $v->options[$key])->unique()->values()->all(),
                ];
            }
        }

        $default = $active->first(fn ($v) => $v->isAvailable($product)) ?? $active->first();

        return ['variants' => $rows->all(), 'groups' => $groups, 'default' => $default, 'initial' => $default->id];
    }

    public function show(Product $product, PriceResolver $resolver)
    {
        abort_unless($product->status === Product::PUBLISHED, 404);

        $product->load(['brand', 'category.parent', 'media', 'specs', 'variants.prices', 'prices']);
        $product->increment('view_count');

        $customer = auth('customer')->user();

        $variantData = $this->variantData($product, $resolver, $customer);

        // برای محصول چندمدلی، قیمت اولیه (و اسکیمای گوگل) از مدل پیش‌فرض می‌آید
        $resolved = $variantData['default']
            ? $resolver->for($variantData['default'], $customer)
            : $resolver->for($product, $customer);

        $product->setRelation('media', $product->media
            ->sortBy(fn ($m) => [$m->is_primary ? 0 : 1, $m->sort, $m->id])->values());

        $related = Product::published()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with(['brand', 'media', 'prices', 'variants.prices'])
            ->limit(8)->get();

        return view('shop.product', [
            'seo' => [
                'title'       => $product->seoTitle(),
                'description' => $product->seoDescription(),
                'og_type'     => 'product',
            ],
            'schema'   => Schema::product($product, $resolved),
            'product'  => $product,
            'resolved' => $resolved,
            'retail'   => $customer?->isWholesaler() ? $resolver->retailFor($product) : null,
            'tierRows' => $customer?->isWholesaler()
                ? $resolver->tiersFor($product, $customer->effectiveTier())
                : collect(),
            'variantData' => $variantData,
            'related'  => $related,
            'reviews'  => $product->reviews()->approved()->latest()->limit(10)->get(),
            'questions' => $product->questions()->answered()->latest()->limit(10)->get(),
        ]);
    }
}
