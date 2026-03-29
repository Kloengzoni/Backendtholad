<?php
// Ajout à placer dans PropertyController::store() et PropertyController::update()
// après qu'un bien est approuvé et rendu disponible.
//
// ── Dans store() ou approve(), après $property->save() ──────────────────────
//
// Notifier tous les clients actifs d'un nouveau bien disponible
// (copier ce bloc dans votre méthode d'approbation)

// Exemple de méthode à ajouter dans PropertyController :

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class PropertyNotificationHelper extends Controller
{
    /**
     * Envoyer une notification à tous les clients actifs
     * quand un nouveau bien est mis en ligne.
     *
     * Appeler cette méthode depuis store() ou depuis une action d'approbation admin.
     */
    public static function notifyNewProperty(Property $property): void
    {
        $title = $property->title ?? 'Nouveau bien disponible';
        $city  = $property->city  ?? '';

        // Récupérer tous les clients actifs (par chunk pour éviter OOM)
        User::where('role', 'client')
            ->where('is_active', true)
            ->select('id')
            ->chunk(200, function ($users) use ($property, $title, $city) {
                $notifications = $users->map(fn($u) => [
                    'user_id'    => $u->id,
                    'title'      => '🏠 Nouveau bien disponible',
                    'body'       => "{$title}" . ($city ? " à {$city}" : '') . " vient d'être mis en ligne !",
                    'type'       => 'property',
                    'data'       => json_encode(['property_id' => $property->id]),
                    'is_read'    => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                Notification::insert($notifications);
            });
    }
}

/*
 * ─── INSTRUCTIONS D'INTÉGRATION ──────────────────────────────────────────────
 *
 * Dans PropertyController.php, dans la méthode store() (ou approve()),
 * après avoir sauvegardé le bien et AVANT le return, ajoutez :
 *
 *   // Si le bien est immédiatement disponible, notifier les clients
 *   if ($property->status === 'disponible' && $property->is_approved) {
 *       PropertyNotificationHelper::notifyNewProperty($property);
 *   }
 *
 * Si vous avez une action d'approbation admin séparée (ex: approve()),
 * ajoutez la même ligne après l'approbation.
 * ─────────────────────────────────────────────────────────────────────────────
 */
