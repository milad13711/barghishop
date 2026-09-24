<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderStatusService;
use App\Services\Pricing\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        config()->set('shop.sms.default', 'log');
    }

    /** محصول با دو رنگ: سفید ۱۰ تومان/۵ عدد، مشکی ۱۲ تومان/۰ عدد */
    protected function productWithColors(): Product
    {
        $product = Product::create([
            'sku' => 'VAR-1', 'name' => 'آیفون چندرنگ', 'status' => Product::PUBLISHED,
            'track_stock' => true, 'stock' => 5,
        ]);

        $retail = PriceTier::retail();

        foreach ([['سفید', 5, 100_000], ['مشکی', 0, 120_000]] as $i => [$color, $stock, $amount]) {
            $v = $product->variants()->create([
                'sku' => "VAR-1-$i", 'name' => $color, 'options' => ['رنگ' => $color],
                'stock' => $stock, 'is_active' => true,
            ]);
            Price::create([
                'priceable_type' => $v->getMorphClass(), 'priceable_id' => $v->id,
                'price_tier_id' => $retail->id, 'min_qty' => 1, 'amount' => $amount,
            ]);
        }

        return $product->fresh('variants.prices');
    }

    public function test_each_variant_has_its_own_price(): void
    {
        $product = $this->productWithColors();
        $resolver = app(PriceResolver::class);

        [$white, $black] = $product->variants;

        $this->assertSame(100_000, $resolver->for($white)->amount);
        $this->assertSame(120_000, $resolver->for($black)->amount);
    }

    public function test_product_page_offers_the_selector_with_per_variant_data(): void
    {
        $product = $this->productWithColors();

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertSee('productBuy', false)
            // داده مدل‌ها به‌صورت JSON درون صفحه می‌رود (حروف فارسی escape می‌شوند)،
            // پس محتوا را از داده ویو می‌سنجیم
            ->assertViewHas('variantData', function (array $data) {
                return collect($data['variants'])->pluck('label')->all() === ['سفید', 'مشکی']
                    && $data['groups'][0]['key'] === 'رنگ'
                    && $data['groups'][0]['values'] === ['سفید', 'مشکی']
                    && $data['variants'][0]['available'] === true
                    && $data['variants'][1]['available'] === false;
            });
    }

    public function test_product_availability_follows_variants(): void
    {
        $product = $this->productWithColors();
        $this->assertTrue($product->isAvailable());

        $product->variants->first()->update(['stock' => 0]);
        $this->assertFalse($product->fresh('variants')->isAvailable());
    }

    public function test_cart_requires_choosing_a_variant(): void
    {
        $product = $this->productWithColors();
        $customer = Customer::create(['mobile' => '09121110000', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);

        $this->actingAs($customer, 'customer')
            ->post(route('cart.add'), ['product_id' => $product->id])
            ->assertSessionHasErrors('variant');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_out_of_stock_variant_cannot_be_added_but_others_can(): void
    {
        $product = $this->productWithColors();
        [$white, $black] = $product->variants;
        $customer = Customer::create(['mobile' => '09121110001', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);

        $this->actingAs($customer, 'customer')
            ->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $black->id])
            ->assertSessionHasErrors('variant');

        $this->actingAs($customer, 'customer')
            ->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $white->id, 'qty' => 2])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, app(CartService::class)->count());
    }

    public function test_quantity_is_limited_to_the_variants_own_stock(): void
    {
        $product = $this->productWithColors();
        $white = $product->variants->first(); // ۵ عدد
        $customer = Customer::create(['mobile' => '09121110002', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);

        $this->actingAs($customer, 'customer')
            ->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $white->id, 'qty' => 6])
            ->assertSessionHasErrors('variant');
    }

    public function test_a_variant_of_another_product_is_rejected(): void
    {
        $product = $this->productWithColors();
        $other = Product::published()->where('sku', '!=', 'VAR-1')->orderBy('id')->firstOrFail();
        $foreign = $other->variants()->create(['sku' => 'FOREIGN-1', 'name' => 'x', 'stock' => 9, 'is_active' => true]);
        $customer = Customer::create(['mobile' => '09121110003', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);

        $this->actingAs($customer, 'customer')
            ->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $foreign->id])
            ->assertSessionHasErrors('variant');
    }

    public function test_order_uses_variant_price_and_deducts_only_that_variants_stock(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'AV']])]);

        $product = $this->productWithColors();
        $white = $product->variants->first();
        $customer = Customer::create(['mobile' => '09121110004', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);
        $address = Address::create([
            'customer_id' => $customer->id, 'receiver_name' => 'تست', 'receiver_mobile' => '09121110004',
            'province_id' => Province::where('slug', 'tehran')->value('id'), 'line' => 'نشانی', 'is_default' => true,
        ]);

        $this->actingAs($customer, 'customer');
        app(CartService::class)->add($product, 2, $white);

        $this->post(route('checkout.place'), [
            'address_id' => $address->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method' => 'online',
        ]);

        $order = Order::latest()->firstOrFail();
        $item = $order->items->first();

        $this->assertSame(100_000, $item->unit_price);
        $this->assertSame($white->id, $item->product_variant_id);
        $this->assertStringContainsString('سفید', $item->name_snapshot);

        $admin = User::firstOrFail();
        app(OrderStatusService::class)->transition($order, Order::PAID, actorType: 'system');
        app(OrderStatusService::class)->transition($order->fresh(), Order::PROCESSING, actorType: 'admin', actorId: $admin->id);

        $this->assertSame(3, $white->fresh()->stock);   // ۵ − ۲
        $this->assertSame(0, $product->variants->last()->fresh()->stock); // مدل دیگر دست‌نخورده
    }

    public function test_checkout_blocks_when_variant_stock_dropped_after_adding_to_cart(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'AV2']])]);

        $product = $this->productWithColors();
        $white = $product->variants->first();
        $customer = Customer::create(['mobile' => '09121110005', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);
        $address = Address::create([
            'customer_id' => $customer->id, 'receiver_name' => 'تست', 'receiver_mobile' => '09121110005',
            'province_id' => Province::where('slug', 'tehran')->value('id'), 'line' => 'نشانی', 'is_default' => true,
        ]);

        $this->actingAs($customer, 'customer');
        app(CartService::class)->add($product, 3, $white);
        $white->update(['stock' => 1]);

        $this->post(route('checkout.place'), [
            'address_id' => $address->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method' => 'online',
        ])->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::count());
    }

    public function test_admin_saves_variants_with_prices_in_toman_and_syncs_total_stock(): void
    {
        $admin = User::firstOrFail();
        $retail = PriceTier::retail();

        $this->actingAs($admin, 'web')->post(route('admin.products.store'), [
            'name' => 'دوربین چندکاناله', 'sku' => 'NVR-1', 'status' => 'published',
            'variants' => [
                ['name' => '۴ کانال', 'options' => 'کانال: ۴', 'stock' => 7, 'is_active' => 1,
                    'prices' => [$retail->id => ['amount' => '1,500,000', 'compare_at' => '1,800,000']]],
                ['name' => '۸ کانال', 'options' => 'کانال: ۸', 'stock' => 3, 'is_active' => 1,
                    'prices' => [$retail->id => ['amount' => '2,400,000']]],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('sku', 'NVR-1')->with('variants.prices')->firstOrFail();

        $this->assertCount(2, $product->variants);
        $this->assertSame(10, $product->stock); // ۷ + ۳
        $this->assertSame(['کانال' => '۴'], $product->variants[0]->options);
        $this->assertSame(15_000_000, app(PriceResolver::class)->for($product->variants[0])->amount);
        $this->assertSame(24_000_000, app(PriceResolver::class)->for($product->variants[1])->amount);
    }

    public function test_admin_can_edit_and_remove_variants(): void
    {
        $product = $this->productWithColors();
        $admin = User::firstOrFail();
        $retail = PriceTier::retail();
        [$white] = $product->variants;

        $this->actingAs($admin, 'web')->post(route('admin.products.update', $product), [
            'name' => $product->name, 'sku' => $product->sku, 'status' => 'published',
            'variants' => [
                ['id' => $white->id, 'name' => 'سفید', 'options' => 'رنگ: سفید', 'stock' => 9, 'is_active' => 1,
                    'prices' => [$retail->id => ['amount' => '20000']]],
            ],
        ])->assertSessionHasNoErrors();

        $product->refresh()->load('variants');

        $this->assertCount(1, $product->variants);            // مشکی حذف شد
        $this->assertSame(9, $product->variants[0]->stock);
        $this->assertSame(9, $product->stock);
        $this->assertSame(200_000, app(PriceResolver::class)->for($product->variants[0])->amount);
    }

    public function test_variant_without_any_retail_price_is_rejected(): void
    {
        $admin = User::firstOrFail();

        $this->actingAs($admin, 'web')->post(route('admin.products.store'), [
            'name' => 'بدون قیمت', 'sku' => 'NOPRICE-1', 'status' => 'draft',
            'variants' => [['name' => 'الف', 'stock' => 1, 'is_active' => 1, 'prices' => []]],
        ])->assertSessionHasErrors('variants');

        $this->assertNull(Product::where('sku', 'NOPRICE-1')->first());
    }

    public function test_listing_card_shows_choose_model_instead_of_direct_add(): void
    {
        $this->productWithColors();

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('انتخاب مدل', false);
    }

    public function test_mobile_drawer_is_wired_to_the_hamburger_event(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString("dispatch('open-menu')", $html);
        $this->assertStringContainsString('open-menu.window', $html);
    }

    public function test_gallery_component_markup_is_rendered_for_products_with_images(): void
    {
        $product = Product::published()->firstOrFail();
        $product->media()->createMany([
            ['path' => 'https://example.com/a.jpg', 'is_primary' => true, 'sort' => 0],
            ['path' => 'https://example.com/b.jpg', 'is_primary' => false, 'sort' => 1],
        ]);

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertSee('productGallery', false)
            ->assertViewHas('product', fn ($p) => $p->media->pluck('path')->all()
                === ['https://example.com/a.jpg', 'https://example.com/b.jpg']);
    }

    public function test_stock_of_a_plain_product_is_deducted_when_processing_starts(): void
    {
        // رگرسیون: commit() قبلاً به‌خاطر بررسی نادرست «قبلاً کسر شده؟» هرگز موجودی را کم نمی‌کرد
        $customer = Customer::create(['mobile' => '09121110006', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true]);
        $product = Product::create(['sku' => 'PLAIN-1', 'name' => 'ساده', 'status' => 'published', 'stock' => 10, 'track_stock' => true]);

        $order = Order::create(['code' => 'T-1', 'customer_id' => $customer->id, 'status' => Order::PAID, 'grand_total' => 1]);
        $order->items()->create(['product_id' => $product->id, 'name_snapshot' => 'ساده', 'qty' => 4, 'unit_price' => 1, 'line_total' => 4]);

        app(OrderStatusService::class)->transition($order, Order::PROCESSING, actorType: 'admin');
        $this->assertSame(6, $product->fresh()->stock);

        // لغو بعد از آماده‌سازی، موجودی را برمی‌گرداند
        app(OrderStatusService::class)->transition($order->fresh(), Order::CANCELLED, actorType: 'admin');
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_admin_uploads_images_per_variant_and_keeps_them_separate_from_general_gallery(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $admin = User::firstOrFail();
        $retail = PriceTier::retail();

        $this->actingAs($admin, 'web')->post(route('admin.products.store'), [
            'name' => 'دوربین رنگی', 'sku' => 'IMG-1', 'status' => 'published',
            'images' => [\Illuminate\Http\UploadedFile::fake()->image('general.jpg')],
            'variants' => [
                ['name' => 'سفید', 'stock' => 3, 'is_active' => 1,
                    'prices' => [$retail->id => ['amount' => '1000000']],
                    'images' => [
                        \Illuminate\Http\UploadedFile::fake()->image('white-front.jpg'),
                        \Illuminate\Http\UploadedFile::fake()->image('white-side.jpg'),
                    ]],
                ['name' => 'مشکی', 'stock' => 3, 'is_active' => 1,
                    'prices' => [$retail->id => ['amount' => '1000000']],
                    'images' => [\Illuminate\Http\UploadedFile::fake()->image('black.jpg')]],
            ],
        ])->assertSessionHasNoErrors();

        $product = Product::where('sku', 'IMG-1')->firstOrFail();
        [$white, $black] = $product->variants;

        $this->assertCount(1, $product->media);            // فقط تصویر عمومی
        $this->assertCount(2, $white->media);
        $this->assertCount(1, $black->media);
        $this->assertCount(4, $product->allMedia);
        $this->assertTrue($white->media->first()->is_primary);
        $this->assertFalse($white->media->last()->is_primary);
        Storage::disk('public')->assertExists($black->media->first()->path);
    }

    public function test_storefront_payload_gives_each_variant_its_own_images_and_falls_back_to_general(): void
    {
        $product = $this->productWithColors();
        [$white, $black] = $product->variants;

        $product->media()->create(['path' => 'products/general.jpg', 'sort' => 0, 'is_primary' => true]);
        $white->media()->create(['product_id' => $product->id, 'path' => 'products/white.jpg', 'sort' => 0, 'is_primary' => true]);

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertViewHas('variantData', function (array $data) {
                return $data['variants'][0]['images'][0]['src'] === '/storage/products/white.jpg'
                    && $data['variants'][1]['images'] === [];      // مشکی: تصویر ندارد → گالری عمومی
            })
            ->assertViewHas('product', fn ($p) => $p->media->pluck('path')->all() === ['products/general.jpg']);
    }

    public function test_primary_image_is_scoped_to_its_own_group(): void
    {
        $product = $this->productWithColors();
        $white = $product->variants->first();
        $admin = User::firstOrFail();

        $general = $product->media()->create(['path' => 'g.jpg', 'sort' => 0, 'is_primary' => true]);
        $w1 = $white->media()->create(['product_id' => $product->id, 'path' => 'w1.jpg', 'sort' => 0, 'is_primary' => true]);
        $w2 = $white->media()->create(['product_id' => $product->id, 'path' => 'w2.jpg', 'sort' => 1, 'is_primary' => false]);

        $this->actingAs($admin, 'web')
            ->post(route('admin.products.media.primary', [$product, $w2->id]))
            ->assertRedirect();

        $this->assertTrue($w2->fresh()->is_primary);
        $this->assertFalse($w1->fresh()->is_primary);
        $this->assertTrue($general->fresh()->is_primary);     // گروه عمومی دست‌نخورده
    }

    public function test_deleting_a_variant_removes_its_images(): void
    {
        $product = $this->productWithColors();
        [$white, $black] = $product->variants;
        $admin = User::firstOrFail();
        $retail = PriceTier::retail();

        $white->media()->create(['product_id' => $product->id, 'path' => 'w.jpg', 'sort' => 0]);

        $this->actingAs($admin, 'web')->post(route('admin.products.update', $product), [
            'name' => $product->name, 'sku' => $product->sku, 'status' => 'published',
            'variants' => [['id' => $black->id, 'name' => 'مشکی', 'stock' => 1, 'is_active' => 1,
                'prices' => [$retail->id => ['amount' => '12000']]]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, \App\Models\ProductMedia::where('path', 'w.jpg')->count());
    }

    public function test_card_image_falls_back_to_a_variant_image_when_no_general_image_exists(): void
    {
        $product = $this->productWithColors();
        $product->variants->first()->media()->create(['product_id' => $product->id, 'path' => 'only-variant.jpg', 'sort' => 0]);

        $this->assertSame('only-variant.jpg', $product->fresh()->primary_image);
    }
}
