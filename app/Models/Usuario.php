<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Usuario extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'celular',
        'password',
        'avatar',
        'rol',
        'visitrd_id',
        'activo',
        'email_verificado_en',
        'saldo_billetera',
        'puntos_lealtad',
        'nivel_lealtad_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verificado_en' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'saldo_billetera' => 'decimal:2',
        'puntos_lealtad' => 'integer',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    public function getUrlAvatarAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->nombre_completo);
    }

    public function getEsAdminAttribute(): bool
    {
        return $this->rol === 'admin';
    }

    public function getEsDuenoRestauranteAttribute(): bool
    {
        return $this->rol === 'dueno_restaurante';
    }

    public function getEsRepartidorAttribute(): bool
    {
        return $this->rol === 'repartidor';
    }

    public function getEsClienteAttribute(): bool
    {
        return $this->rol === 'cliente';
    }

    // =============================================
    // FILAMENT
    // =============================================

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->es_admin,
            'restaurante' => $this->es_dueno_restaurante,
            'repartidor' => $this->es_repartidor,
            default => false,
        };
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function restaurantes()
    {
        return $this->hasMany(Restaurante::class, 'dueno_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'usuario_id');
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class, 'usuario_id');
    }

    public function carrito()
    {
        return $this->hasOne(Carrito::class, 'usuario_id');
    }

    public function repartidor()
    {
        return $this->hasOne(Repartidor::class, 'usuario_id');
    }

    public function metodosPago()
    {
        return $this->hasMany(MetodoPago::class, 'usuario_id');
    }

    public function nivelLealtad()
    {
        return $this->belongsTo(NivelLealtad::class, 'nivel_lealtad_id');
    }

    public function notificaciones()
    {
        return $this->morphMany(Notificacion::class, 'notificable');
    }

    public function cuponesUsados()
    {
        return $this->hasMany(UsoCupon::class, 'usuario_id');
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function agregarPuntosLealtad(int $puntos, string $descripcion, ?int $pedidoId = null): void
    {
        $this->increment('puntos_lealtad', $puntos);

        $this->transaccionesLealtad()->create([
            'tipo' => 'ganancia',
            'puntos' => $puntos,
            'descripcion' => $descripcion,
            'pedido_id' => $pedidoId,
        ]);

        $this->verificarNivelLealtad();
    }

    public function usarPuntosLealtad(int $puntos, string $descripcion, ?int $pedidoId = null): bool
    {
        if ($this->puntos_lealtad < $puntos) {
            return false;
        }

        $this->decrement('puntos_lealtad', $puntos);

        $this->transaccionesLealtad()->create([
            'tipo' => 'canje',
            'puntos' => -$puntos,
            'descripcion' => $descripcion,
            'pedido_id' => $pedidoId,
        ]);

        return true;
    }

    protected function verificarNivelLealtad(): void
    {
        $nuevoNivel = NivelLealtad::where('puntos_minimos', '<=', $this->puntos_lealtad)
            ->orderByDesc('puntos_minimos')
            ->first();

        if ($nuevoNivel && $nuevoNivel->id !== $this->nivel_lealtad_id) {
            $this->update(['nivel_lealtad_id' => $nuevoNivel->id]);
        }
    }

    public function agregarSaldoBilletera(float $monto, string $tipo, string $descripcion, ?int $referencia = null): void
    {
        $this->increment('saldo_billetera', $monto);

        $this->transaccionesBilletera()->create([
            'tipo' => $tipo,
            'monto' => $monto,
            'descripcion' => $descripcion,
            'referencia_id' => $referencia,
        ]);
    }

    public function tieneRestauranteFavorito(int $restauranteId): bool
    {
        return $this->favoritos()
            ->where('favoritable_type', Restaurante::class)
            ->where('favoritable_id', $restauranteId)
            ->exists();
    }

    public function tienePlatoFavorito(int $platoId): bool
    {
        return $this->favoritos()
            ->where('favoritable_type', Plato::class)
            ->where('favoritable_id', $platoId)
            ->exists();
    }
}
