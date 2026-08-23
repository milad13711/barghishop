<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = collect();

        $urls->push(['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily']);
        $urls->push(['loc' => route('shop.index'), 'priority' => '0.9', 'changefreq' => 'daily']);
        $urls->push(['loc' => route('blog.index'), 'priority' => '0.8', 'changefreq' => 'weekly']);
        $urls->push(['loc' => route('wholesale'), 'priority' => '0.7', 'changefreq' => 'monthly']);
        $urls->push(['loc' => route('pages.about'), 'priority' => '0.5', 'changefreq' => 'monthly']);
        $urls->push(['loc' => route('pages.contact'), 'priority' => '0.5', 'changefreq' => 'monthly']);

        foreach (Category::active()->get() as $category) {
            $urls->push(['loc' => route('shop.category', $category), 'priority' => '0.8',
                         'lastmod' => $category->updated_at?->toAtomString(), 'changefreq' => 'weekly']);
        }

        foreach (Brand::active()->get() as $brand) {
            $urls->push(['loc' => route('shop.brand', $brand), 'priority' => '0.7',
                         'lastmod' => $brand->updated_at?->toAtomString(), 'changefreq' => 'weekly']);
        }

        foreach (Product::published()->get() as $product) {
            $urls->push(['loc' => route('shop.product', $product), 'priority' => '0.9',
                         'lastmod' => $product->updated_at?->toAtomString(), 'changefreq' => 'daily']);
        }

        foreach (Post::published()->get() as $post) {
            $urls->push(['loc' => route('blog.show', $post), 'priority' => '0.7',
                         'lastmod' => $post->updated_at?->toAtomString(), 'changefreq' => 'monthly']);
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
