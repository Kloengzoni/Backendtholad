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

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES — ImmoStay
|--------------------------------------------------------------------------
| Panel sécurisé Laravel (session-based)
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /*
    |────────────────────────────
    | GUEST (non connecté)
    |────────────────────────────
    */
    Route::middleware('guest:admin')->group(function () {

        Route::get('login', [AuthController::class, 'showLogin'])
            ->name('login');

        Route::post('login', [AuthController::class, 'login'])
            ->name('login.post');
    });

    /*
    |────────────────────────────
    | AUTHENTICATED ADMIN
    |────────────────────────────
    |
    | ⚠️ IMPORTANT FIX:
    | Laravel standard = auth:admin (pas auth.admin)
    | sauf si tu as custom middleware
    */
    Route::middleware('auth:admin')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])
            ->name('logout');

        /*
        | DASHBOARD
        */
        Route::get('/', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        | PROPERTIES
        */
        Route::prefix('properties')->group(function () {
            Route::get('/', [PropertyController::class, 'index'])->name('properties.index');
            Route::get('{id}', [PropertyController::class, 'show'])->name('properties.show');
            Route::put('{id}/approve', [PropertyController::class, 'approve'])->name('properties.approve');
            Route::delete('{id}', [PropertyController::class, 'destroy'])->name('properties.destroy');
        });

        /*
        | BOOKINGS
        */
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index'])->name('bookings.index');
            Route::get('{ref}', [BookingController::class, 'show'])->name('bookings.show');
            Route::put('{ref}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
            Route::put('{ref}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
        });

        /*
        | PAYMENTS
        */
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('payments.index');
            Route::post('{ref}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
        });

        /*
        | USERS
        */
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::get('{id}', [UserController::class, 'show'])->name('users.show');
            Route::put('{id}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        });

        /*
        | REVIEWS
        */
        Route::prefix('reviews')->group(function () {
            Route::get('/', [ReviewController::class, 'index'])->name('reviews.index');
            Route::delete('{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
            Route::put('{id}/toggle', [ReviewController::class, 'toggle'])->name('reviews.toggle');
        });

        /*
        | ACCOUNTING
        */
        Route::get('accounting', [AccountingController::class, 'index'])
            ->name('accounting.index');

        /*
        | SUPPORT
        */
        Route::prefix('support')->group(function () {
            Route::get('/', [SupportController::class, 'index'])->name('support.index');
            Route::get('{id}', [SupportController::class, 'show'])->name('support.show');
            Route::post('{id}/reply', [SupportController::class, 'reply'])->name('support.reply');
            Route::put('{id}/close', [SupportController::class, 'close'])->name('support.close');
        });

        /*
        | SETTINGS
        */
        Route::get('settings', fn () => view('admin.settings'))
            ->name('settings');
    });
});