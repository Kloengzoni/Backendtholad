<?php
// ─── DIFF à appliquer dans routes/api.php ────────────────────────────────────
//
// Ajouter l'import en haut du fichier :
// use App\Http\Controllers\Api\SupportController;
//
// Dans le groupe middleware('auth:sanctum'), ajouter :
//
//   /*
//   | SUPPORT
//   */
//   Route::get('support/agent', [SupportController::class, 'agent']);
//
// ─── Version complète du groupe auth pour référence ──────────────────────────

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
use App\Http\Controllers\Api\SupportController; // ← AJOUTER

Route::prefix('v1')->group(function () {

    // PUBLIC
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

    // WEBHOOK PEEXIT — Public
    Route::post('payments/peex/callback', [PaymentController::class, 'peexCallback'])
        ->withoutMiddleware(['auth:sanctum']);

    // PROTECTED
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // PROFILE
        Route::prefix('profile')->group(function () {
            Route::get('/',        [ProfileController::class, 'show']);
            Route::put('/',        [ProfileController::class, 'update']);
            Route::post('avatar',  [ProfileController::class, 'updateAvatar']);
            Route::put('password', [ProfileController::class, 'changePassword']);
        });

        // ── FIX: SUPPORT AGENT ─────────────────────────────────────────────
        // Retourne l'UUID de l'admin/agent pour que Flutter puisse
        // envoyer des messages au bon destinataire (évite erreur 422)
        Route::get('support/agent', [SupportController::class, 'agent']);
        // ──────────────────────────────────────────────────────────────────

        // PROPERTIES
        Route::post('properties',             [PropertyController::class, 'store']);
        Route::put('properties/{id}',         [PropertyController::class, 'update']);
        Route::delete('properties/{id}',      [PropertyController::class, 'destroy']);
        Route::post('properties/{id}/images', [PropertyController::class, 'uploadImages']);

        // BOOKINGS
        Route::prefix('bookings')->group(function () {
            Route::get('/',             [BookingController::class, 'index']);
            Route::post('/',            [BookingController::class, 'store']);
            Route::get('{ref}',         [BookingController::class, 'show']);
            Route::put('{ref}/cancel',  [BookingController::class, 'cancel']);
            Route::put('{ref}/confirm', [BookingController::class, 'confirm']);
        });

        // PAYMENTS
        Route::prefix('payments')->group(function () {
            Route::post('initiate',      [PaymentController::class, 'initiate']);
            Route::get('{ref}/status',   [PaymentController::class, 'status']);
        });

        // FAVORITES
        Route::get('favorites',          [FavoriteController::class, 'index']);
        Route::post('favorites/{id}',    [FavoriteController::class, 'toggle']);

        // MESSAGES
        Route::get('messages',           [MessageController::class, 'conversations']);
        Route::get('messages/{userId}',  [MessageController::class, 'thread']);
        Route::post('messages',          [MessageController::class, 'send']);
        Route::put('messages/{id}/read', [MessageController::class, 'markRead']);

        // REVIEWS
        Route::get('reviews',            [ReviewController::class, 'index']);
        Route::post('reviews',           [ReviewController::class, 'store']);
        Route::put('reviews/{id}',       [ReviewController::class, 'update']);
        Route::delete('reviews/{id}',    [ReviewController::class, 'destroy']);

        // NOTIFICATIONS
        Route::get('notifications',             [NotificationController::class, 'index']);
        Route::put('notifications/read-all',    [NotificationController::class, 'readAll']);
        Route::put('notifications/{id}/read',   [NotificationController::class, 'markRead']);
    });
});
