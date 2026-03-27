<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('admin');

        // Si pas connecté
        if (!$guard->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            return redirect()->route('admin.login');
        }

        $user = $guard->user();

        // Sécurité role (évite null crash)
        if (($user->role ?? null) !== 'admin') {
            $guard->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied'
                ], 403);
            }

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Accès réservé aux administrateurs.']);
        }

        // Check account actif sécurisé
        if (!($user->is_active ?? false)) {
            $guard->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account suspended'
                ], 403);
            }

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Compte administrateur suspendu.']);
        }

        return $next($request);
    }
}