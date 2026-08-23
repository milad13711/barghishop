<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;

/** مدیریت دسته‌بندی‌ها و برندها — هر دو ساختار یکسانی دارند. */
class TaxonomyController extends Controller
{
    public function categories()
    {
        return view('admin.categories.index', [
            'categories' => Category::with('parent')->withCount('products')->orderBy('sort')->get(),
            'parents'    => Category::roots()->orderBy('sort')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $this->validateCategory($request);

        Category::create($data + ['is_active' => $request->boolean('is_active', true),
                                  'show_in_menu' => $request->boolean('show_in_menu', true),
                                  'prices_require_login' => $request->boolean('prices_require_login')]);

        return back()->with('success', 'دسته‌بندی ساخته شد.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $category->update($this->validateCategory($request, $category) + [
            'is_active'            => $request->boolean('is_active'),
            'show_in_menu'         => $request->boolean('show_in_menu'),
            'prices_require_login' => $request->boolean('prices_require_login'),
        ]);

        return back()->with('success', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroyCategory(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'این دسته محصول دارد و قابل حذف نیست.']);
        }

        $category->delete();

        return back()->with('success', 'دسته‌بندی حذف شد.');
    }

    protected function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'parent_id'       => ['nullable', 'exists:categories,id'],
            'sort'            => ['nullable', 'integer', 'min:0'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'seo_title'       => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function brands()
    {
        return view('admin.brands.index', [
            'brands' => Brand::withCount('products')->orderBy('sort')->get(),
        ]);
    }

    public function storeBrand(Request $request)
    {
        Brand::create($this->validateBrand($request) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'برند ساخته شد.');
    }

    public function updateBrand(Request $request, Brand $brand)
    {
        $brand->update($this->validateBrand($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'برند به‌روزرسانی شد.');
    }

    protected function validateBrand(Request $request): array
    {
        return $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'sort'            => ['nullable', 'integer', 'min:0'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'seo_title'       => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
