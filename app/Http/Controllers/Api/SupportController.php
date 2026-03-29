<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * SupportController
 *
 * GET /api/v1/support/agent
 * → Retourne l'UUID et le nom du compte admin/support
 *   pour que l'app Flutter puisse envoyer des messages au bon destinataire.
 *
 * FIX: évite l'erreur 422 "receiver_id invalide" causée par la valeur
 *      en dur "support" dans ApiConstants.supportUserId (qui n'est pas un UUID).
 */
class SupportController extends Controller
{
    public function agent(Request $request)
    {
        // Trouver le premier compte admin actif
        $admin = User::where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$admin) {
            // Fallback : chercher un owner/agent s'il n'y a pas d'admin
            $admin = User::whereIn('role', ['owner', 'agent'])
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
        }

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Agent support non disponible.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'     => $admin->id,
                'name'   => $admin->name ?? 'TholadImmo Support',
                'avatar' => $admin->avatar_url,
            ],
        ]);
    }
}
