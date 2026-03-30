<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * SupportController
 *
 * GET /api/v1/support/agent
 *
 * FIX BUG 2a :
 *   L'ancien code faisait ->where('is_active', true).
 *   Or la migration crée is_active DEFAULT false, et les admins existants
 *   créés avant le dernier seeder peuvent avoir is_active = 0.
 *   → Le endpoint retournait 503 → Flutter envoyait receiver_id = '' → 422.
 *
 *   SOLUTION : chercher par rôle seulement, sans filtrer is_active.
 *   L'admin est toujours disponible même s'il n'est pas "connecté".
 */
class SupportController extends Controller
{
    public function agent(Request $request)
    {
        // FIX : plus de filtre ->where('is_active', true)
        // On cherche par rôle uniquement, priorité admin > owner/agent
        $admin = User::where('role', 'admin')
            ->orderBy('id')
            ->first();

        if (!$admin) {
            // Fallback : owner ou agent s'il n'y a pas d'admin
            $admin = User::whereIn('role', ['owner', 'agent'])
                ->orderBy('id')
                ->first();
        }

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Agent support non disponible.',
            ], 503);
        }

        // Construire l'URL absolue de l'avatar si besoin
        $avatarUrl = $admin->avatar;
        if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
            $appUrl    = rtrim(config('app.url', 'https://backendtholad-production.up.railway.app'), '/');
            $avatarUrl = $appUrl . '/storage/' . $avatarUrl;
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'     => $admin->id,
                'name'   => $admin->name ?? 'TholadImmo Support',
                'avatar' => $avatarUrl,
            ],
        ]);
    }
}
