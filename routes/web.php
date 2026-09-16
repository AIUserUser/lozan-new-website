<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UtilityController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackStorefront;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [UtilityController::class, 'sitemap']);
Route::get('/robots.txt', [UtilityController::class, 'robots']);
Route::post('/telegram/webhook', [UtilityController::class, 'telegramWebhook']);

Route::get('/admin/login', [LoginController::class, 'show'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'store']);
Route::post('/admin/logout', [LoginController::class, 'destroy'])->name('admin.logout');

Route::middleware(EnsureAdmin::class)->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.orders'));
    Route::get('/analytics', AnalyticsController::class)->name('admin.analytics');
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('admin.orders.update');
    Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products');
    Route::get('/products/new', [AdminProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
});

$storeRoutes = function () {
    Route::get('/', HomeController::class);
    Route::get('/shop', ShopController::class);
    Route::get('/product/{slug}', [ProductController::class, 'show']);
    Route::post('/product/{slug}/cart', [ProductController::class, 'addToCart']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'remove']);
    Route::get('/checkout', [CheckoutController::class, 'show']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::match(['get', 'post'], '/order-confirmation', OrderConfirmationController::class);
};

Route::middleware([SetLocale::class.':ar', TrackStorefront::class])->group($storeRoutes);
Route::prefix('en')->middleware([SetLocale::class.':en', TrackStorefront::class])->group($storeRoutes);
