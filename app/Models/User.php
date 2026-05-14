<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'direccion',
        'avatar',
        'activo',
        'gurztac_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * Determina si el usuario puede acceder a un panel de Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Panel de administración
        if ($panel->getId() === 'admin') {
            return $this->hasRole(['super_admin', 'admin', 'administrador']) ||
                   $this->email === 'admin@sazonrd.com';
        }

        // Panel de restaurante
        if ($panel->getId() === 'restaurant') {
            return $this->hasRole(['restaurante', 'restaurant', 'gerente', 'manager']) ||
                   $this->restaurante !== null;
        }

        // Panel de delivery
        if ($panel->getId() === 'delivery') {
            return $this->hasRole(['delivery', 'repartidor', 'driver']);
        }

        return false;
    }

    /**
     * Relación con restaurante (si es dueño/gerente)
     */
    public function restaurante()
    {
        return $this->hasOne(Restaurante::class, 'user_id');
    }

    /**
     * Relación con personal
     */
    public function personal()
    {
        return $this->hasOne(Personal::class);
    }

    /**
     * Pedidos del usuario como cliente
     */
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    /**
     * Verificar si es super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin') || $this->email === 'admin@sazonrd.com';
    }

    /**
     * Verificar si es admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin', 'administrador']);
    }

    /**
     * Nombre para mostrar
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->email;
    }
}