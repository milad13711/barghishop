<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Seo\Schema;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('pages.home', [
            'seo' => [
                'title'       => Setting::get('home_seo_title', config('shop.name')),
                'description' => Setting::get('home_seo_description', ''),
            ],
            'schema'     => Schema::organization(),
            'banners'    => Banner::live('home_hero')->get(),
            'categories' => Category::active()->roots()->orderBy('sort')->get(),
            'featured'   => Product::published()->where('is_featured', true)
                                ->with(['brand', 'media', 'prices', 'category'])
                                ->latest('published_at')->limit(8)->get(),
            'newest'     => Product::published()
                                ->with(['brand', 'media', 'prices', 'category'])
                                ->latest('published_at')->limit(8)->get(),
            'bestSellers' => Product::published()
                                ->with(['brand', 'media', 'prices', 'category'])
                                ->orderByDesc('sold_count')->limit(8)->get(),
            'brands'     => Brand::active()->orderBy('sort')->get(),
            'posts'      => Post::published()->with('category')->latest('published_at')->limit(3)->get(),
        ]);
    }
}
