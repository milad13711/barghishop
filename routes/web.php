<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Shop;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| فروشگاه (عمومی)
|--------------------------------------------------------------------------
*/
Route::get('/', Shop\HomeController::class)->name('home');

Route::get('/shop', [Shop\CatalogController::class, 'index'])->name('shop.index');
Route::get('/search', [Shop\CatalogController::class, 'search'])->name('search');
Route::get('/category/{category}', [Shop\CatalogController::class, 'category'])->name('shop.category');
Route::get('/brand/{brand}', [Shop\CatalogController::class, 'brand'])->name('shop.brand');
Route::get('/product/{product}', [Shop\CatalogController::class, 'show'])->name('shop.product');

Route::get('/blog', [Shop\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [Shop\BlogController::class, 'show'])->name('blog.show');

Route::get('/about', [Shop\PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [Shop\PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [Shop\PageController::class, 'storeContact'])->name('pages.contact.store');
Route::get('/wholesale', [Shop\PageController::class, 'wholesale'])->name('wholesale');

Route::get('/sitemap.xml', Shop\SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| سبد خرید
|--------------------------------------------------------------------------
*/
Route::controller(Shop\CartController::class)->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/add', 'add')->name('add');
    Route::post('/coupon', 'applyCoupon')->name('coupon.apply');
    Route::delete('/coupon', 'removeCoupon')->name('coupon.remove');
    Route::patch('/{item}', 'update')->name('update');
    Route::delete('/{item}', 'remove')->name('remove');
});

/*
|--------------------------------------------------------------------------
| تسویه حساب و پرداخت
|--------------------------------------------------------------------------
*/
Route::middleware('auth:customer')->controller(Shop\CheckoutController::class)
    ->prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/address', 'storeAddress')->name('address');
        Route::post('/place', 'place')->name('place');
        Route::get('/result/{order}', 'result')->name('result');
    });

// بازگشت از درگاه — بدون میدل‌ور auth چون کاربر از سایت بانک برمی‌گردد
Route::get('/payment/callback/{gateway}', [Shop\PaymentController::class, 'callback'])
    ->name('payment.callback');

/*
|--------------------------------------------------------------------------
| ورود مشتری (موبایل + کد یکبار مصرف)
|--------------------------------------------------------------------------
*/
Route::controller(OtpLoginController::class)->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', 'form')->name('auth.login');
        Route::post('/login', 'sendCode')->name('auth.send-code');
        Route::get('/verify', 'verifyForm')->name('auth.verify.form');
        Route::post('/verify', 'verify')->name('auth.verify');
    });

    Route::post('/logout', 'logout')->middleware('auth:customer')->name('auth.logout');
});

/*
|--------------------------------------------------------------------------
| داشبورد مشتری
|--------------------------------------------------------------------------
*/
Route::middleware('auth:customer')->prefix('account')->name('account.')->group(function () {
    Route::get('/', Account\DashboardController::class)->name('dashboard');
    Route::get('/orders', [Account\OrderController::class, 'index'])->name('orders');
    Route::get('/orders/{order}', [Account\OrderController::class, 'show'])->name('orders.show');
    Route::get('/loyalty', Account\LoyaltyController::class)->name('loyalty');
    Route::get('/wholesale', [Account\WholesaleController::class, 'form'])->name('wholesale');
    Route::post('/wholesale', [Account\WholesaleController::class, 'store'])->name('wholesale.store');
});

/*
|--------------------------------------------------------------------------
| پنل مدیریت
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:web')->controller(Admin\LoginController::class)->group(function () {
        Route::get('/login', 'form')->name('login');
        Route::post('/login', 'login')->name('login.store');
    });

    Route::middleware('auth:web')->group(function () {
        Route::post('/logout', [Admin\LoginController::class, 'logout'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::patch('/customers/{customer}', [Admin\CustomerController::class, 'update'])->name('customers.update');

        Route::get('/products', [Admin\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [Admin\ProductController::class, 'create'])->name('products.create');
        Route::get('/products/import', [Admin\ImportController::class, 'form'])->name('products.import');
        Route::post('/products/import', [Admin\ImportController::class, 'store'])->name('products.import.store');
        Route::get('/products/import/template', [Admin\ImportController::class, 'template'])->name('products.import.template');
        Route::post('/products', [Admin\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
        Route::post('/products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [Admin\ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/products/{product}/media/{media}', [Admin\ProductController::class, 'deleteMedia'])->name('products.media.destroy');

        Route::get('/categories', [Admin\TaxonomyController::class, 'categories'])->name('categories.index');
        Route::post('/categories', [Admin\TaxonomyController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [Admin\TaxonomyController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [Admin\TaxonomyController::class, 'destroyCategory'])->name('categories.destroy');

        Route::get('/brands', [Admin\TaxonomyController::class, 'brands'])->name('brands.index');
        Route::post('/brands', [Admin\TaxonomyController::class, 'storeBrand'])->name('brands.store');
        Route::patch('/brands/{brand}', [Admin\TaxonomyController::class, 'updateBrand'])->name('brands.update');

        Route::get('/posts', [Admin\PostController::class, 'index'])->name('posts.index');
        Route::get('/posts/create', [Admin\PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [Admin\PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [Admin\PostController::class, 'edit'])->name('posts.edit');
        Route::post('/posts/{post}', [Admin\PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [Admin\PostController::class, 'destroy'])->name('posts.destroy');

        Route::get('/coupons', [Admin\CouponController::class, 'index'])->name('coupons.index');
        Route::post('/coupons', [Admin\CouponController::class, 'store'])->name('coupons.store');
        Route::patch('/coupons/{coupon}', [Admin\CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [Admin\CouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
