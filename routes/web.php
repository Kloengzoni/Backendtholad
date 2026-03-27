<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
| Projet : ImmoStay — Tholad Group
| API + Admin Panel
| Compatible Railway / Production
*/

/**
 * 🔥 HEALTH CHECK / ROOT
 * IMPORTANT : Railway utilise "/" pour vérifier le service
 */
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'ImmoStay — Tholad Group',
        'version' => '1.0.0'
    ]);
});

/**
 * 🩺 HEALTH ENDPOINT (monitoring externe)
 */
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'running'
    ]);
});

/**
 * 🔐 ADMIN ENTRY POINT (safe fallback)
 * ⚠️ Ne casse pas si route admin.login n’existe pas
 */
Route::get('/admin', function () {
    // Si tu as un panel admin plus tard, remplace ceci
    return response()->json([
        'status' => 'admin-entry',
        'message' => 'Admin panel available'
    ]);
});

/**
 * 🚫 FALLBACK 404 WEB (optionnel mais propre)
 */
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Route not found'
    ], 404);
});