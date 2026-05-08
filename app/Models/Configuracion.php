<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'grupo',
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'es_publica',
    ];

    protected $casts = [
        'es_publica' => 'boolean',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getValorTipadoAttribute()
    {
        return match ($this->tipo) {
            'booleano' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'numero' => is_numeric($this->valor) ? (float) $this->valor : 0,
            'json' => json_decode($this->valor, true),
            default => $this->valor,
        };
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePublicas($query)
    {
        return $query->where('es_publica', true);
    }

    public function scopePorGrupo($query, string $grupo)
    {
        return $query->where('grupo', $grupo);
    }

    // =============================================
    // MÉTODOS ESTÁTICOS
    // =============================================

    public static function obtener(string $clave, $default = null)
    {
        $cacheKey = "config_{$clave}";

        return Cache::remember($cacheKey, 3600, function () use ($clave, $default) {
            $config = static::where('clave', $clave)->first();
            return $config ? $config->valor_tipado : $default;
        });
    }

    public static function establecer(string $clave, $valor, string $tipo = 'texto', string $grupo = 'general'): self
    {
        if ($tipo === 'json' && is_array($valor)) {
            $valor = json_encode($valor);
        } elseif ($tipo === 'booleano') {
            $valor = $valor ? 'true' : 'false';
        }

        $config = static::updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => $valor,
                'tipo' => $tipo,
                'grupo' => $grupo,
            ]
        );

        Cache::forget("config_{$clave}");

        return $config;
    }

    public static function obtenerGrupo(string $grupo): array
    {
        $cacheKey = "config_grupo_{$grupo}";

        return Cache::remember($cacheKey, 3600, function () use ($grupo) {
            return static::where('grupo', $grupo)
                ->get()
                ->pluck('valor_tipado', 'clave')
                ->toArray();
        });
    }

    public static function limpiarCache(?string $clave = null): void
    {
        if ($clave) {
            Cache::forget("config_{$clave}");
        } else {
            // Limpiar todo el cache de configuraciones
            $configs = static::all();
            foreach ($configs as $config) {
                Cache::forget("config_{$config->clave}");
            }
        }
    }

    // =============================================
    // CONFIGURACIONES COMUNES
    // =============================================

    public static function comisionPlataforma(): float
    {
        return static::obtener('comision_plataforma', 15);
    }

    public static function itbis(): float
    {
        return static::obtener('itbis', 18);
    }

    public static function puntosPorPeso(): float
    {
        return static::obtener('puntos_por_peso', 0.01); // 1 punto por cada RD$100
    }

    public static function tiempoExpiracionCarrito(): int
    {
        return static::obtener('tiempo_expiracion_carrito', 60); // minutos
    }

    public static function emailSoporte(): string
    {
        return static::obtener('email_soporte', 'soporte@sazonrd.com');
    }

    public static function telefonoSoporte(): string
    {
        return static::obtener('telefono_soporte', '809-000-0000');
    }

    public static function whatsappSoporte(): string
    {
        return static::obtener('whatsapp_soporte', '18090000000');
    }
}
