<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // Si c'est une requête API → pas de redirect
        if ($request->expectsJson()) {
            return null;
        }

        // Admin panel
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        // Web fallback (évite crash si route inexistante)
        if (route('login', [], false)) {
            return route('login');
        }

        // fallback ultime sécurisé
        return '/login';
    }
}