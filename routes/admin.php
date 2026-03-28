<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\SupportController;
// ── Nouveaux contrôleurs ──
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\StockController;

Route::prefix('admin')->name('admin.')->group(function () {

    // ── GUEST ──────────────────────────────────────────────────
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
    });

    // ── AUTHENTICATED ADMIN ─────────────────────────────────────
    Route::middleware('auth:admin')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // DASHBOARD
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // PROPERTIES
        Route::prefix('properties')->group(function () {
            Route::get('/',              [PropertyController::class, 'index'])->name('properties.index');
            Route::get('create',         [PropertyController::class, 'create'])->name('properties.create');
            Route::post('/',             [PropertyController::class, 'store'])->name('properties.store');
            Route::get('{id}',           [PropertyController::class, 'show'])->name('properties.show');
            Route::get('{id}/edit',      [PropertyController::class, 'edit'])->name('properties.edit');
            Route::put('{id}',           [PropertyController::class, 'update'])->name('properties.update');
            Route::put('{id}/approve',   [PropertyController::class, 'approve'])->name('properties.approve');
            Route::delete('{id}',        [PropertyController::class, 'destroy'])->name('properties.destroy');
        });

        // BOOKINGS
        Route::prefix('bookings')->group(function () {
            Route::get('/',               [BookingController::class, 'index'])->name('bookings.index');
            Route::get('{ref}',           [BookingController::class, 'show'])->name('bookings.show');
            Route::put('{ref}/confirm',   [BookingController::class, 'confirm'])->name('bookings.confirm');
            Route::put('{ref}/complete',  [BookingController::class, 'complete'])->name('bookings.complete');
        });

        // PAYMENTS
        Route::prefix('payments')->group(function () {
            Route::get('/',              [PaymentController::class, 'index'])->name('payments.index');
            Route::post('{ref}/refund',  [PaymentController::class, 'refund'])->name('payments.refund');
        });

        // USERS
        Route::prefix('users')->group(function () {
            Route::get('/',          [UserController::class, 'index'])->name('users.index');
            Route::get('{id}',       [UserController::class, 'show'])->name('users.show');
            Route::put('{id}/toggle',[UserController::class, 'toggle'])->name('users.toggle');
        });

        // ── PROPRIÉTAIRES ──────────────────────────────────────
        Route::prefix('owners')->name('owners.')->group(function () {
            Route::get('/',              [OwnerController::class, 'index'])->name('index');
            Route::get('create',         [OwnerController::class, 'create'])->name('create');
            Route::post('/',             [OwnerController::class, 'store'])->name('store');
            Route::get('{id}',           [OwnerController::class, 'show'])->name('show');
            Route::get('{id}/edit',      [OwnerController::class, 'edit'])->name('edit');
            Route::put('{id}',           [OwnerController::class, 'update'])->name('update');
            Route::put('{id}/verify',    [OwnerController::class, 'verify'])->name('verify');
            Route::put('{id}/toggle',    [OwnerController::class, 'toggle'])->name('toggle');
        });

        // ── AGENTS THOLAD ──────────────────────────────────────
        Route::prefix('agents')->name('agents.')->group(function () {
            Route::get('/',              [AgentController::class, 'index'])->name('index');
            Route::get('create',         [AgentController::class, 'create'])->name('create');
            Route::post('/',             [AgentController::class, 'store'])->name('store');
            Route::get('{id}',           [AgentController::class, 'show'])->name('show');
            Route::get('{id}/edit',      [AgentController::class, 'edit'])->name('edit');
            Route::put('{id}',           [AgentController::class, 'update'])->name('update');
            Route::put('{id}/toggle',    [AgentController::class, 'toggle'])->name('toggle');
            Route::delete('{id}',        [AgentController::class, 'destroy'])->name('destroy');
        });

        // ── GESTION DES STOCKS ─────────────────────────────────
        Route::prefix('stock')->name('stock.')->group(function () {
            Route::get('/',                     [StockController::class, 'index'])->name('index');
            Route::get('create',                [StockController::class, 'create'])->name('create');
            Route::post('/',                    [StockController::class, 'store'])->name('store');
            Route::get('alerts',                [StockController::class, 'alerts'])->name('alerts');
            Route::get('movements',             [StockController::class, 'movements'])->name('movements');
            Route::get('{id}',                  [StockController::class, 'show'])->name('show');
            Route::get('{id}/edit',             [StockController::class, 'edit'])->name('edit');
            Route::put('{id}',                  [StockController::class, 'update'])->name('update');
            Route::post('{id}/add',             [StockController::class, 'addStock'])->name('add');
            Route::post('{id}/remove',          [StockController::class, 'removeStock'])->name('remove');
            Route::put('alerts/{id}/resolve',   [StockController::class, 'resolveAlert'])->name('alerts.resolve');
        });

        // REVIEWS
        Route::prefix('reviews')->group(function () {
            Route::get('/',          [ReviewController::class, 'index'])->name('reviews.index');
            Route::delete('{id}',    [ReviewController::class, 'destroy'])->name('reviews.destroy');
            Route::put('{id}/toggle',[ReviewController::class, 'toggle'])->name('reviews.toggle');
        });

        // ACCOUNTING
        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');

        // SUPPORT
        Route::prefix('support')->group(function () {
            Route::get('/',          [SupportController::class, 'index'])->name('support.index');
            Route::get('{id}',       [SupportController::class, 'show'])->name('support.show');
            Route::post('{id}/reply',[SupportController::class, 'reply'])->name('support.reply');
            Route::put('{id}/close', [SupportController::class, 'close'])->name('support.close');
        });

        // SETTINGS
        Route::get('settings', fn () => view('admin.settings'))->name('settings');
    });
});
