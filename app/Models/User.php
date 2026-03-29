<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable {
        Notifiable::notifications as laravelNotifications;
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country_code',
        'country',
        'password',
        'avatar',
        'role',
        'is_verified',
        'is_active',
        'otp_code',
        'otp_expires_at',
        'device_token',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code'];

    protected $casts = [
        'is_verified'    => 'boolean',
        'is_active'      => 'boolean',
        'otp_expires_at' => 'datetime',
        'last_login_at'  => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────
    public function properties()     { return $this->hasMany(Property::class, 'owner_id'); }
    public function bookings()       { return $this->hasMany(Booking::class); }
    public function favorites()      { return $this->hasMany(Favorite::class); }
    public function reviews()        { return $this->hasMany(Review::class); }
    public function notifications()  { return $this->hasMany(Notification::class); }
    public function payments()       { return $this->hasMany(Payment::class); }
    public function supportTickets() { return $this->hasMany(SupportTicket::class); }

    /**
     * FIX — Relation propriétaire (manquait dans le fichier original)
     */
    public function ownerProfile()
    {
        return $this->hasOne(OwnerProfile::class);
    }

    // ── Helpers ──────────────────────────────────────────────────
    public function isAdmin()  { return $this->role === 'admin'; }
    public function isOwner()  { return $this->role === 'owner'; }
    public function isClient() { return $this->role === 'client'; }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=b8860b&color=fff&size=64';
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        if (count($parts) >= 2) {
            return strtoupper($parts[0][0] . $parts[1][0]);
        }
        return strtoupper(substr($this->name, 0, 2));
    }
}
