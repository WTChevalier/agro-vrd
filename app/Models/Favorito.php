<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorito extends Model
{
    use HasFactory;

    protected $table = 'favoritos';

    protected $fillable = [
        'usuario_id',
        'favoritable_type',
        'favoritable_id',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function favoritable()
    {
        return $this->morphTo();
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getEsRestauranteAttribute(): bool
    {
        return $this->favoritable_type === Restaurante::class;
    }

    public function getEsPlatoAttribute(): bool
    {
        return $this->favoritable_type === Plato::class;
    }

    public function getNombreAttribute(): string
    {
        return $this->favoritable?->nombre ?? '';
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeRestaurantes($query)
    {
        return $query->where('favoritable_type', Restaurante::class);
    }

    public function scopePlatos($query)
    {
        return $query->where('favoritable_type', Plato::class);
    }

    // =============================================
    // MÉTODOS ESTÁTICOS
    // =============================================

    public static function alternar(int $usuarioId, string $tipo, int $id): bool
    {
        $existente = static::where('usuario_id', $usuarioId)
            ->where('favoritable_type', $tipo)
            ->where('favoritable_id', $id)
            ->first();

        if ($existente) {
            $existente->delete();
            return false; // Ya no es favorito
        }

        static::create([
            'usuario_id' => $usuarioId,
            'favoritable_type' => $tipo,
            'favoritable_id' => $id,
        ]);

        return true; // Ahora es favorito
    }
}
