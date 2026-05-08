<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenPush extends Model
{
    use HasFactory;

    protected $table = 'tokens_push';

    protected $fillable = [
        'usuario_id',
        'token',
        'plataforma',
        'id_dispositivo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getEsIosAttribute(): bool
    {
        return $this->plataforma === 'ios';
    }

    public function getEsAndroidAttribute(): bool
    {
        return $this->plataforma === 'android';
    }

    public function getEsWebAttribute(): bool
    {
        return $this->plataforma === 'web';
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorPlataforma($query, string $plataforma)
    {
        return $query->where('plataforma', $plataforma);
    }

    public function scopeIos($query)
    {
        return $query->porPlataforma('ios');
    }

    public function scopeAndroid($query)
    {
        return $query->porPlataforma('android');
    }

    public function scopeWeb($query)
    {
        return $query->porPlataforma('web');
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function desactivar(): void
    {
        $this->update(['activo' => false]);
    }

    public function activar(): void
    {
        $this->update(['activo' => true]);
    }

    public static function registrar(int $usuarioId, string $token, string $plataforma, ?string $idDispositivo = null): self
    {
        return static::updateOrCreate(
            [
                'usuario_id' => $usuarioId,
                'token' => $token,
            ],
            [
                'plataforma' => $plataforma,
                'id_dispositivo' => $idDispositivo,
                'activo' => true,
            ]
        );
    }
}
