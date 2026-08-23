<?php

use App\Http\Controllers\Account;
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
