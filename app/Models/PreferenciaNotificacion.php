<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreferenciaNotificacion extends Model
{
    use HasFactory;

    protected $table = 'preferencias_notificaciones';

    protected $fillable = [
        'usuario_id',
        'email_estado_pedido',
        'email_promociones',
        'email_boletin',
        'push_estado_pedido',
        'push_promociones',
        'push_ofertas_cercanas',
        'sms_estado_pedido',
    ];

    protected $casts = [
        'email_estado_pedido' => 'boolean',
        'email_promociones' => 'boolean',
        'email_boletin' => 'boolean',
        'push_estado_pedido' => 'boolean',
        'push_promociones' => 'boolean',
        'push_ofertas_cercanas' => 'boolean',
        'sms_estado_pedido' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function debeRecibirEmail(string $tipo): bool
    {
        return match ($tipo) {
            'estado_pedido' => $this->email_estado_pedido,
            'promociones' => $this->email_promociones,
            'boletin' => $this->email_boletin,
            default => false,
        };
    }

    public function debeRecibirPush(string $tipo): bool
    {
        return match ($tipo) {
            'estado_pedido' => $this->push_estado_pedido,
            'promociones' => $this->push_promociones,
            'ofertas_cercanas' => $this->push_ofertas_cercanas,
            default => false,
        };
    }

    public function debeRecibirSms(string $tipo): bool
    {
        return match ($tipo) {
            'estado_pedido' => $this->sms_estado_pedido,
            default => false,
        };
    }

    public static function obtenerParaUsuario(int $usuarioId): self
    {
        return static::firstOrCreate(
            ['usuario_id' => $usuarioId],
            [
                'email_estado_pedido' => true,
                'email_promociones' => true,
                'email_boletin' => false,
                'push_estado_pedido' => true,
                'push_promociones' => true,
                'push_ofertas_cercanas' => false,
                'sms_estado_pedido' => false,
            ]
        );
    }

    public function desactivarTodo(): void
    {
        $this->update([
            'email_estado_pedido' => false,
            'email_promociones' => false,
            'email_boletin' => false,
            'push_estado_pedido' => false,
            'push_promociones' => false,
            'push_ofertas_cercanas' => false,
            'sms_estado_pedido' => false,
        ]);
    }

    public function activarEsenciales(): void
    {
        $this->update([
            'email_estado_pedido' => true,
            'push_estado_pedido' => true,
        ]);
    }
}
