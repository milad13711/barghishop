<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\Seo\Schema;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::published()
            ->with('category')
            ->when($request->query('category'), fn ($q, $slug) =>
                $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('q'), fn ($q, $term) =>
                $q->where('title', 'like', "%$term%"))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('blog.index', [
            'seo' => [
                'title'       => 'مقالات و راهنمای خرید | '.config('shop.name'),
                'description' => 'راهنمای خرید، آموزش نصب و عیب‌یابی آیفون تصویری و تجهیزات برق ساختمان.',
            ],
            'posts'      => $posts,
            'categories' => PostCategory::withCount('posts')->orderBy('sort')->get(),
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->status === 'published', 404);

        $post->load(['category', 'products.media', 'products.prices', 'products.brand']);
        $post->increment('view_count');

        $schema = [Schema::article($post)];

        if ($faq = Schema::faq($post->faq ?? [])) {
            $schema[] = $faq;
        }

        return view('blog.show', [
            'seo' => [
                'title'       => $post->seoTitle(),
                'description' => $post->seoDescription(),
                'og_type'     => 'article',
            ],
            'schema' => $schema,
            'post'   => $post,
            'related' => Post::published()
                ->where('post_category_id', $post->post_category_id)
                ->whereKeyNot($post->id)
                ->latest('published_at')->limit(3)->get(),
        ]);
    }
}
