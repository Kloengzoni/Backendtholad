<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FIX : $timestamps = false mais created_at existe en base (useCurrent).
 * On garde $timestamps = false pour ne pas laisser Eloquent gérer updated_at
 * (qui n'existe pas), mais on définit CREATED_AT pour qu'Eloquent
 * remplisse created_at automatiquement à la création.
 */
class Message extends Model
{
    // FIX : ne gérer QUE created_at (updated_at n'existe pas dans la table)
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'conversation_id', 'sender_id', 'content',
        'type', 'attachment_url', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    // FIX : remplir created_at automatiquement à la création
    protected static function booted(): void
    {
        static::creating(function ($message) {
            if (empty($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function sender()       { return $this->belongsTo(User::class, 'sender_id'); }
}
