<?php
// database/migrations/2026_03_30_000001_fix_admin_users_is_active.php
//
// FIX BUG 2a :
//   Les admins créés avant ce patch ont is_active = 0 (valeur par défaut).
//   SupportController::agent() filtrait sur is_active = true → retournait 503
//   → Flutter envoyait receiver_id = '' → erreur 422 "receiver_id invalide".
//
//   Cette migration met is_active = true pour tous les admins existants.
//   ATTENTION : à appliquer en production avec `php artisan migrate`.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Activer tous les utilisateurs avec rôle admin, owner, ou agent
        DB::table('users')
            ->whereIn('role', ['admin', 'owner', 'agent'])
            ->where('is_active', false)
            ->update(['is_active' => true]);

        // S'assurer aussi que les admins vérifiés sont bien is_verified = true
        DB::table('users')
            ->where('role', 'admin')
            ->update(['is_verified' => true, 'is_active' => true]);
    }

    public function down(): void
    {
        // Pas de rollback — on ne peut pas savoir qui était actif avant
    }
};
