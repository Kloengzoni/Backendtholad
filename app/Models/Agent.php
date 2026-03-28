<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Agent extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'user_id','name','email','phone','avatar','password',
        'role','department','employee_id','hire_date','salary','salary_currency',
        'contract_type',
        'can_manage_properties','can_manage_bookings','can_manage_payments',
        'can_manage_stock','can_view_reports','can_manage_users','can_manage_agents',
        'emergency_contact_name','emergency_contact_phone','emergency_contact_relation',
        'address','city','country',
        'id_document_type','id_document_number',
        'status','notes','last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'hire_date'              => 'date',
        'last_login_at'          => 'datetime',
        'can_manage_properties'  => 'boolean',
        'can_manage_bookings'    => 'boolean',
        'can_manage_payments'    => 'boolean',
        'can_manage_stock'       => 'boolean',
        'can_view_reports'       => 'boolean',
        'can_manage_users'       => 'boolean',
        'can_manage_agents'      => 'boolean',
        'salary'                 => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getRoleLabels(): array
    {
        return [
            'agent_commercial' => 'Agent Commercial',
            'gestionnaire'     => 'Gestionnaire',
            'comptable'        => 'Comptable',
            'technicien'       => 'Technicien',
            'superviseur'      => 'Superviseur',
            'directeur'        => 'Directeur',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->getRoleLabels()[$this->role] ?? $this->role;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0047FF&color=fff&size=64';
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        return count($parts) >= 2
            ? strtoupper($parts[0][0] . $parts[1][0])
            : strtoupper(substr($this->name, 0, 2));
    }

    public function scopeActive($q) { return $q->where('status', 'actif'); }
}
