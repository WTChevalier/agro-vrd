<?php

/**
 * Modelo: AuditoriaLogin
 *
 * Sprint 132B — Una entrada por cada intento de login al panel.
 */

namespace App\Models;

use App\Concerns\Anonimizable;
use Illuminate\Database\Eloquent\Model;

class AuditoriaLogin extends Model
{
    use Anonimizable; // Sprint 141B — GDPR Art. 17

    protected $table = 'auditoria_login';

    /**
     * Sprint 141B — Campos PII en logs de login. ip se reemplaza por
     * 0.0.0.0 (preserva el row pero rompe correlación con persona);
     * user_agent puede contener fingerprints; email_intento es directamente
     * identificador.
     */
    protected array $piiFields = [
        'email_intento' => 'email',
        'ip' => 'ip',
        'user_agent' => 'string',
    ];

    protected $fillable = [
        'usuario_id', 'email_intento', 'exito', 'motivo',
        'ip', 'user_agent',
    ];

    protected $casts = [
        'exito' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Helper: count fallos recientes para una IP. Útil para detectar
     * brute force.
     */
    public static function fallosRecientes(string $ip, int $minutos = 15): int
    {
        return static::where('ip', $ip)
            ->where('exito', false)
            ->where('created_at', '>=', now()->subMinutes($minutos))
            ->count();
    }
}
