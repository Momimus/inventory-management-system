<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LowStockController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

// auth.session signs out other sessions once the account's password changes.
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::resource('categories', CategoryController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);

    // {type} is bound to the StockTransactionType enum, so unknown types return 404.
    // There are deliberately no edit, update or delete routes for transactions.
    Route::get('stock', [StockTransactionController::class, 'index'])->name('stock.index');
    Route::get('stock/{type}/create', [StockTransactionController::class, 'create'])->name('stock.create');
    Route::post('stock/{type}', [StockTransactionController::class, 'store'])->name('stock.store');
    Route::get('stock/transactions/{transaction}', [StockTransactionController::class, 'show'])->name('stock.show');

    Route::get('low-stock', LowStockController::class)->name('low-stock');

    Route::middleware('can:view-reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('movements/{type}', [ReportController::class, 'movements'])->name('movements');
        Route::get('low-stock', [ReportController::class, 'lowStock'])->name('low-stock');
        Route::get('product-history', [ReportController::class, 'productHistory'])->name('product-history');
    });

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
});
