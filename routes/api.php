<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;

/*
|--------------------------------------------------------------------------
| ImmoStay API Routes — v1
|--------------------------------------------------------------------------
| Flutter / Mobile / Production Ready (Railway compatible)
*/

Route::prefix('v1')->group(function () {

    /*
    |────────────────────────────
    | PUBLIC ROUTES
    |────────────────────────────
    */

    Route::prefix('auth')->group(function () {
        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('send-otp',        [AuthController::class, 'sendOtp']);
        Route::post('verify-otp',      [AuthController::class, 'verifyOtp']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',  [AuthController::class, 'resetPassword']);
    });

    Route::get('properties',               [PropertyController::class, 'index']);
    Route::get('properties/featured',      [PropertyController::class, 'featured']);
    Route::get('properties/{id}',          [PropertyController::class, 'show']);
    Route::get('properties/{id}/reviews',  [ReviewController::class, 'propertyReviews']);

    /*
    |────────────────────────────────────────────────────────
    | WEBHOOK PEEXIT — Public (sécurisé par Basic Auth Peexit)
    | URL à configurer dans votre tableau de bord Peexit :
    | https://votre-app.railway.app/api/v1/payments/peex/callback
    |────────────────────────────────────────────────────────
    */
    Route::post('payments/peex/callback', [PaymentController::class, 'peexCallback'])
        ->withoutMiddleware(['auth:sanctum']);

    /*
    |────────────────────────────
    | PROTECTED ROUTES (SANCTUM)
    |────────────────────────────
    */

    Route::middleware('auth:sanctum')->group(function () {

        /*
        | AUTH
        */
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        /*
        | PROFILE
        */
        Route::prefix('profile')->group(function () {
            Route::get('/',        [ProfileController::class, 'show']);
            Route::put('/',        [ProfileController::class, 'update']);
            Route::post('avatar',  [ProfileController::class, 'updateAvatar']);
            Route::put('password', [ProfileController::class, 'changePassword']);
        });

        /*
        | PROPERTIES (agent/admin)
        */
        Route::post('properties',             [PropertyController::class, 'store']);
        Route::put('properties/{id}',         [PropertyController::class, 'update']);
        Route::delete('properties/{id}',      [PropertyController::class, 'destroy']);
        Route::post('properties/{id}/images', [PropertyController::class, 'uploadImages']);

        /*
        | BOOKINGS
        */
        Route::prefix('bookings')->group(function () {
            Route::get('/',            [BookingController::class, 'index']);
            Route::post('/',           [BookingController::class, 'store']);
            Route::get('{ref}',        [BookingController::class, 'show']);
            Route::put('{ref}/cancel', [BookingController::class, 'cancel']);
            Route::put('{ref}/confirm',[BookingController::class, 'confirm']);
        });

        /*
        | PAYMENTS
        */
        Route::prefix('payments')->group(function () {
            Route::post('initiate',    [PaymentController::class, 'initiate']);
            Route::get('{ref}/status', [PaymentController::class, 'status']);
            // Anciens callbacks MTN/Airtel directs (conservés pour compatibilité)
            Route::post('mtn/callback',    [PaymentController::class, 'mtnCallback'])->withoutMiddleware(['auth:sanctum']);
            Route::post('airtel/callback', [PaymentController::class, 'airtelCallback'])->withoutMiddleware(['auth:sanctum']);
        });

        /*
        | FAVORITES
        */
        Route::get('favorites',       [FavoriteController::class, 'index']);
        Route::post('favorites/{id}', [FavoriteController::class, 'toggle']);

        /*
        | MESSAGES
        */
        Route::prefix('messages')->group(function () {
            Route::get('/',         [MessageController::class, 'conversations']);
            Route::get('{userId}',  [MessageController::class, 'thread']);
            Route::post('/',        [MessageController::class, 'send']);
            Route::put('{id}/read', [MessageController::class, 'markRead']);
        });

        /*
        | REVIEWS
        */
        Route::post('reviews',     [ReviewController::class, 'store']);
        Route::put('reviews/{id}', [ReviewController::class, 'update']);

        /*
        | NOTIFICATIONS
        */
        Route::prefix('notifications')->group(function () {
            Route::get('/',         [NotificationController::class, 'index']);
            Route::put('read-all',  [NotificationController::class, 'readAll']);
            Route::put('{id}/read', [NotificationController::class, 'markRead']);
        });
    });
});
