<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_home_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('برقی‌شاپ', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_shop_listing_renders(): void
    {
        $this->get(route('shop.index'))->assertOk();
    }

    public function test_product_page_renders_with_schema(): void
    {
        $product = Product::published()->firstOrFail();

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertSee($product->name, false)
            ->assertSee('"@type":"Product"', false);
    }

    public function test_product_slug_stays_persian(): void
    {
        $product = Product::published()->firstOrFail();

        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $product->slug);
    }

    public function test_blog_pages_render(): void
    {
        $this->get(route('blog.index'))->assertOk();

        $post = Post::published()->firstOrFail();

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('"@type":"Article"', false);
    }

    public function test_search_finds_products_by_name(): void
    {
        $this->get(route('search', ['q' => 'سیماران']))
            ->assertOk()
            ->assertSee('سیماران', false);
    }

    public function test_static_pages_render(): void
    {
        foreach ([route('pages.about'), route('pages.contact'), route('wholesale'), route('cart.index')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sitemap_lists_products(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<urlset', false);
    }

    public function test_account_requires_login(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('auth.login'));
    }
}
