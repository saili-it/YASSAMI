<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------ Boutique
Route::get('/', HomeController::class)->name('home');
Route::get('/boutique', [ShopController::class, 'index'])->name('shop.index');
Route::get('/boutique/{category:slug}', [ShopController::class, 'index'])->name('shop.category');
Route::get('/produit/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::view('/panier', 'pages.cart')->name('cart');

// ----------------------------------------------------------------- Commande
// Limite stricte : chaque commande réserve du stock.
Route::post('/commande', [OrderController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('orders.store');
Route::get('/commande/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');

// ----------------------------------------------------------- Administration
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/connexion', [AuthController::class, 'create'])->name('login');
        Route::post('/connexion', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    });

    Route::middleware('admin')->group(function () {
        Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        // Liaison par id (le slug peut changer pendant l'édition).
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/nouvelle', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category:id}/modifier', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category:id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category:id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/produits', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/produits/nouveau', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/produits', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/produits/{product:id}/modifier', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/produits/{product:id}', [AdminProductController::class, 'update'])->name('products.update');
        Route::patch('/produits/{product:id}/basculer/{field}', [AdminProductController::class, 'toggle'])
            ->whereIn('field', ['is_active', 'is_featured'])
            ->name('products.toggle');
        Route::delete('/produits/{product:id}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/commandes', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{order:id}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('/commandes/{order:id}', [AdminOrderController::class, 'update'])->name('orders.update');

        Route::get('/parametres', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/parametres', [SettingsController::class, 'update'])->name('settings.update');
    });
});
