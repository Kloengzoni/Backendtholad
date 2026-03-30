<?php
// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  FICHIER : app/Http/Controllers/Admin/MessageController.php              ║
// ║  BUG CORRIGÉ : L'admin ne voyait pas les messages Flutter                ║
// ║  CAUSE : l'admin utilisait SupportTickets, Flutter utilisait             ║
// ║           conversations/messages → deux systèmes déconnectés             ║
// ╚══════════════════════════════════════════════════════════════════════════╝

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  GET /admin/messages
    //  Liste toutes les conversations triées par dernier message reçu
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $conversations = Conversation::with(['user1', 'user2', 'property'])
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return view('admin.messages.index', compact('conversations'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /admin/messages/{id}
    //  Affiche le fil de messages d'une conversation + marque comme lus
    // ─────────────────────────────────────────────────────────────────────────
    public function show(string $id)
    {
        $activeConv = Conversation::with(['user1', 'user2', 'property'])
            ->findOrFail($id);

        $messages = Message::where('conversation_id', $activeConv->id)
            ->with('sender:id,name,role,avatar')
            ->orderBy('created_at')
            ->get();

        // Marquer les messages du client comme lus (côté admin)
        // Le compte support (role=admin dans users) est user2 dans les convs créées par les clients
        $supportUser = User::where('role', 'admin')->orderBy('id')->first();
        if ($supportUser) {
            if ($activeConv->user1_id === $supportUser->id) {
                $activeConv->update(['user1_unread' => 0]);
            } elseif ($activeConv->user2_id === $supportUser->id) {
                $activeConv->update(['user2_unread' => 0]);
            }
            // Marquer les messages non lus comme lus en base
            Message::where('conversation_id', $activeConv->id)
                ->where('sender_id', '!=', $supportUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        // Recharger la liste sidebar
        $conversations = Conversation::with(['user1', 'user2', 'property'])
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return view('admin.messages.index', compact('conversations', 'activeConv', 'messages'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /admin/messages/{id}/reply
    //  L'admin envoie un message → visible immédiatement dans l'app Flutter
    //  grâce au polling toutes les 5s dans chat_screen.dart
    // ─────────────────────────────────────────────────────────────────────────
    public function reply(Request $request, string $id)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $conv = Conversation::findOrFail($id);

        // Retrouver le user "support" dans la table users (role=admin)
        // C'est ce compte que Flutter utilise via GET /api/v1/support/agent
        $supportUser = User::where('role', 'admin')->orderBy('id')->first();

        if (!$supportUser) {
            return back()->withErrors([
                'error' => 'Aucun compte support (role=admin) trouvé dans la table users. '
                         . 'Exécutez : php artisan db:seed --class=SupportUserSeeder',
            ]);
        }

        $now = now();

        // Créer le message admin dans la conversation
        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $supportUser->id,
            'content'         => $request->content,
            'type'            => 'text',
        ]);

        // Incrémenter les non-lus côté CLIENT (pas côté admin)
        if ($conv->user2_id === $supportUser->id) {
            // Support est user2 → incrémenter user1_unread (= le client)
            $conv->increment('user1_unread');
        } else {
            // Support est user1 ou n'est pas dans la conv → incrémenter user2_unread
            $conv->increment('user2_unread');
        }

        // Mettre à jour le résumé de la conversation
        $conv->update([
            'last_message'    => $request->content,
            'last_message_at' => $now,
        ]);

        return redirect()
            ->route('admin.messages.show', $conv->id)
            ->with('success', 'Message envoyé. Le client le recevra dans les 5 secondes (polling Flutter).');
    }
}