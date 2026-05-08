<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tipo',
        'notificable_type',
        'notificable_id',
        'datos',
        'leido_en',
    ];

    protected $casts = [
        'datos' => 'array',
        'leido_en' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notificacion) {
            if (empty($notificacion->id)) {
                $notificacion->id = (string) Str::uuid();
            }
        });
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getEstaLeidaAttribute(): bool
    {
        return $this->leido_en !== null;
    }

    public function getTituloAttribute(): string
    {
        return $this->datos['titulo'] ?? 'Notificación';
    }

    public function getMensajeAttribute(): string
    {
        return $this->datos['mensaje'] ?? '';
    }

    public function getIconoAttribute(): string
    {
        return $this->datos['icono'] ?? 'fas-bell';
    }

    public function getUrlAttribute(): ?string
    {
        return $this->datos['url'] ?? null;
    }

    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function notificable()
    {
        return $this->morphTo();
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeNoLeidas($query)
    {
        return $query->whereNull('leido_en');
    }

    public function scopeLeidas($query)
    {
        return $query->whereNotNull('leido_en');
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function marcarLeida(): void
    {
        if (!$this->esta_leida) {
            $this->update(['leido_en' => now()]);
        }
    }

    public function marcarNoLeida(): void
    {
        $this->update(['leido_en' => null]);
    }

    public static function crearPara($notificable, string $tipo, array $datos): self
    {
        return static::create([
            'tipo' => $tipo,
            'notificable_type' => get_class($notificable),
            'notificable_id' => $notificable->id,
            'datos' => $datos,
        ]);
    }

    public static function marcarTodasLeidas($notificable): int
    {
        return static::where('notificable_type', get_class($notificable))
            ->where('notificable_id', $notificable->id)
            ->whereNull('leido_en')
            ->update(['leido_en' => now()]);
    }

    public static function contarNoLeidas($notificable): int
    {
        return static::where('notificable_type', get_class($notificable))
            ->where('notificable_id', $notificable->id)
            ->whereNull('leido_en')
            ->count();
    }
}
