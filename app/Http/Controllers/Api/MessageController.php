<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MessageController (API)
 *
 * FIX BUG 2b :
 *   Quand une conversation est créée, last_message_at était NULL.
 *   Le dashboard admin trie par last_message_at DESC → une conversation
 *   avec last_message_at = NULL n'apparaissait pas ou apparaissait en dernier.
 *
 *   De plus, Message::$timestamps = false mais le model caste created_at.
 *   La migration crée la colonne avec useCurrent() → la valeur est bien là
 *   en base, mais $message->created_at peut être null juste après create()
 *   car Eloquent ne recharge pas les colonnes DEFAULT. On fait un fresh().
 */
class MessageController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    //  GET /api/v1/messages  — liste des conversations du client
    // ────────────────────────────────────────────────────────────────────────
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::with(['user1', 'user2'])
            ->where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) use ($userId) {
                $partner = $conv->user1_id === $userId ? $conv->user2 : $conv->user1;
                $unread  = $conv->user1_id === $userId ? $conv->user1_unread : $conv->user2_unread;

                return [
                    'conversation_id' => $conv->id,
                    'user_id'         => $partner?->id,
                    'name'            => $partner?->name ?? 'Support',
                    'last_message'    => $conv->last_message,
                    'time'            => $conv->last_message_at
                        ? $conv->last_message_at->format('H:i')
                        : '',
                    'unread'          => $unread ?? 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  GET /api/v1/messages/{userId}  — fil de discussion
    // ────────────────────────────────────────────────────────────────────────
    public function thread(Request $request, string $userId)
    {
        $me   = $request->user()->id;
        $conv = Conversation::where(function ($q) use ($me, $userId) {
            $q->where('user1_id', $me)->where('user2_id', $userId);
        })->orWhere(function ($q) use ($me, $userId) {
            $q->where('user1_id', $userId)->where('user2_id', $me);
        })->first();

        if (!$conv) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $messages = Message::where('conversation_id', $conv->id)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'content'    => $m->content,
                'type'       => $m->type,
                'is_mine'    => $m->sender_id === $me,
                'is_read'    => $m->is_read,
                'created_at' => $m->created_at,
            ]);

        // Marquer comme lu côté client
        if ($conv->user1_id === $me) {
            $conv->update(['user1_unread' => 0]);
        } else {
            $conv->update(['user2_unread' => 0]);
        }

        return response()->json(['success' => true, 'data' => $messages]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/messages  — envoyer un message
    // ────────────────────────────────────────────────────────────────────────
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content'     => 'required|string|max:2000',
            'booking_id'  => 'nullable|exists:bookings,id',
        ]);

        $me         = $request->user()->id;
        $receiverId = $request->receiver_id;
        $now        = now();

        // Trouver ou créer la conversation
        $conv = Conversation::where(function ($q) use ($me, $receiverId) {
            $q->where('user1_id', $me)->where('user2_id', $receiverId);
        })->orWhere(function ($q) use ($me, $receiverId) {
            $q->where('user1_id', $receiverId)->where('user2_id', $me);
        })->first();

        if (!$conv) {
            // FIX BUG 2b : toujours initialiser last_message_at à now()
            // sinon la conversation n'apparaît pas dans le dashboard admin
            $conv = Conversation::create([
                'user1_id'        => $me,
                'user2_id'        => $receiverId,
                'booking_id'      => $request->booking_id,
                'last_message'    => $request->content,
                'last_message_at' => $now,   // ← FIX : ne pas laisser NULL
            ]);
        }

        $message = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $me,
            'content'         => $request->content,
            'type'            => 'text',
        ]);

        // FIX BUG 2b : Message a timestamps=false + useCurrent() en migration
        // → created_at peut être null en mémoire juste après create().
        // On recharge depuis la DB pour avoir la vraie valeur.
        $message->refresh();

        // Incrémenter unread pour le destinataire
        if ($conv->user1_id === $receiverId) {
            $conv->increment('user1_unread');
        } else {
            $conv->increment('user2_unread');
        }

        $conv->update([
            'last_message'    => $request->content,
            'last_message_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $message->id,
                'content'    => $message->content,
                'type'       => $message->type,
                'is_mine'    => true,
                'is_read'    => false,
                'created_at' => $message->created_at ?? $now,
            ],
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  PUT /api/v1/messages/{id}/read
    // ────────────────────────────────────────────────────────────────────────
    public function markRead(Request $request, string $id)
    {
        Message::where('id', $id)->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
