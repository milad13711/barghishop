<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.posts.index', [
            'posts' => Post::with('category')
                ->when($request->query('q'), fn ($q, $t) => $q->where('title', 'like', "%$t%"))
                ->latest()->paginate(25)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.posts.form', ['post' => new Post, 'categories' => PostCategory::orderBy('name')->get()]);
    }

    public function edit(Post $post)
    {
        return view('admin.posts.form', ['post' => $post, 'categories' => PostCategory::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $post = Post::create($this->data($request));

        return redirect()->route('admin.posts.edit', $post)->with('success', 'مقاله ساخته شد.');
    }

    public function update(Request $request, Post $post)
    {
        $post->update($this->data($request, $post));

        return back()->with('success', 'مقاله ذخیره شد.');
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'مقاله حذف شد.');
    }

    protected function data(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:250'],
            'post_category_id' => ['nullable', 'exists:post_categories,id'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'body'             => ['nullable', 'string'],
            'status'           => ['required', 'in:draft,published'],
            'seo_title'        => ['nullable', 'string', 'max:200'],
            'seo_description'  => ['nullable', 'string', 'max:500'],
            'faq'              => ['nullable', 'array'],
            'faq.*.q'          => ['nullable', 'string', 'max:300'],
            'faq.*.a'          => ['nullable', 'string', 'max:2000'],
        ]);

        $data['faq'] = collect($data['faq'] ?? [])
            ->filter(fn ($row) => filled($row['q'] ?? null) && filled($row['a'] ?? null))
            ->values()->all() ?: null;

        $data['author_id'] = $post?->author_id ?? auth()->id();

        if ($data['status'] === 'published' && ! $post?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
