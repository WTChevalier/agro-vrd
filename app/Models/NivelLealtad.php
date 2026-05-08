<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelLealtad extends Model
{
    use HasFactory;

    protected $table = 'niveles_lealtad';

    protected $fillable = [
        'nombre',
        'puntos_minimos',
        'icono',
        'color',
        'multiplicador_puntos',
        'descuento_delivery',
        'beneficios',
        'orden',
    ];

    protected $casts = [
        'puntos_minimos' => 'integer',
        'multiplicador_puntos' => 'decimal:2',
        'descuento_delivery' => 'decimal:2',
        'beneficios' => 'array',
        'orden' => 'integer',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getDescuentoDeliveryFormateadoAttribute(): string
    {
        return number_format($this->descuento_delivery, 0) . '%';
    }

    public function getMultiplicadorFormateadoAttribute(): string
    {
        return $this->multiplicador_puntos . 'x';
    }

    public function getBeneficiosListaAttribute(): array
    {
        return $this->beneficios ?? [];
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'nivel_lealtad_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeOrdenadosPorPuntos($query)
    {
        return $query->orderBy('puntos_minimos');
    }

    // =============================================
    // MÉTODOS ESTÁTICOS
    // =============================================

    public static function obtenerParaPuntos(int $puntos): ?self
    {
        return static::where('puntos_minimos', '<=', $puntos)
            ->orderByDesc('puntos_minimos')
            ->first();
    }

    public static function siguienteNivel(int $puntosActuales): ?self
    {
        return static::where('puntos_minimos', '>', $puntosActuales)
            ->orderBy('puntos_minimos')
            ->first();
    }
}
