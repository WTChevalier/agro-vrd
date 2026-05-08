<?php

namespace App\Services;

use App\Models\Restaurante;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\HistorialSuscripcion;
use App\Notifications\SuscripcionPorVencer;
use App\Notifications\SuscripcionVencida;
use App\Notifications\SuscripcionActivada;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Gestión de Suscripciones
 *
 * Maneja la creación, renovación, cancelación y verificación de suscripciones
 */
class GestorSuscripcionesService
{
    /**
     * Días de gracia antes de suspender
     */
    protected int $diasGracia = 7;

    /**
     * Días de anticipación para avisar renovación
     */
    protected int $diasAvisoRenovacion = 7;

    /**
     * Crear nueva suscripción para un restaurante
     */
    public function crear(
        Restaurante $restaurante,
        Plan $plan,
        string $ciclo = 'mensual',
        ?float $descuentoPorcentaje = null,
        ?float $descuentoMonto = null
    ): Suscripcion {
        // Cancelar suscripción anterior si existe
        if ($restaurante->suscripcionActiva) {
            $this->cancelar($restaurante->suscripcionActiva, 'Cambio de plan');
        }

        // Calcular precios
        $precioBase = $ciclo === 'anual' ? $plan->precio_anual : $plan->precio_mensual;
        $precioFinal = $this->calcularPrecioFinal($precioBase, $descuentoPorcentaje, $descuentoMonto);

        // Calcular fechas
        $fechaInicio = now();
        $fechaFin = $ciclo === 'anual' ? now()->addYear() : now()->addMonth();

        // Crear suscripción
        $suscripcion = Suscripcion::create([
            'restaurante_id' => $restaurante->id,
            'plan_id' => $plan->id,
            'tipo_ciclo' => $ciclo,
            'precio_actual' => $precioBase,
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_monto' => $descuentoMonto,
            'precio_final' => $precioFinal,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_proxima_facturacion' => $fechaFin,
            'estado' => $plan->es_gratuito ? 'activa' : 'pendiente',
            'renovacion_automatica' => true,
        ]);

        // Actualizar restaurante
        $restaurante->update([
            'plan_id' => $plan->id,
            'suscripcion_activa_id' => $suscripcion->id,
        ]);

        // Registrar en historial
        HistorialSuscripcion::create([
            'suscripcion_id' => $suscripcion->id,
            'plan_anterior_id' => null,
            'plan_nuevo_id' => $plan->id,
            'tipo_cambio' => 'nueva',
            'precio_anterior' => 0,
            'precio_nuevo' => $precioFinal,
            'fecha_cambio' => now(),
        ]);

        return $suscripcion;
    }

    /**
     * Activar suscripción (después de pago)
     */
    public function activar(Suscripcion $suscripcion): Suscripcion
    {
        $suscripcion->update([
            'estado' => 'activa',
        ]);

        // Actualizar estado de cuenta del restaurante
        $suscripcion->restaurante->update([
            'estado_cuenta' => 'al_dia',
            'ultimo_pago_at' => now(),
            'dias_mora' => 0,
        ]);

        // Notificar al restaurante
        // $suscripcion->restaurante->propietario->notify(new SuscripcionActivada($suscripcion));

        return $suscripcion;
    }

    /**
     * Renovar suscripción existente
     */
    public function renovar(Suscripcion $suscripcion, ?Plan $nuevoPlan = null): Suscripcion
    {
        $plan = $nuevoPlan ?? $suscripcion->plan;
        $precioBase = $suscripcion->tipo_ciclo === 'anual' ? $plan->precio_anual : $plan->precio_mensual;

        // Mantener descuentos si aplican
        $precioFinal = $this->calcularPrecioFinal(
            $precioBase,
            $suscripcion->descuento_porcentaje,
            $suscripcion->descuento_monto
        );

        // Calcular nueva fecha fin
        $nuevaFechaFin = $suscripcion->tipo_ciclo === 'anual'
            ? Carbon::parse($suscripcion->fecha_fin)->addYear()
            : Carbon::parse($suscripcion->fecha_fin)->addMonth();

        // Registrar en historial
        HistorialSuscripcion::create([
            'suscripcion_id' => $suscripcion->id,
            'plan_anterior_id' => $suscripcion->plan_id,
            'plan_nuevo_id' => $plan->id,
            'tipo_cambio' => $nuevoPlan ? 'cambio_plan' : 'renovacion',
            'precio_anterior' => $suscripcion->precio_final,
            'precio_nuevo' => $precioFinal,
            'fecha_cambio' => now(),
        ]);

        // Actualizar suscripción
        $suscripcion->update([
            'plan_id' => $plan->id,
            'fecha_inicio' => $suscripcion->fecha_fin,
            'fecha_fin' => $nuevaFechaFin,
            'fecha_proxima_facturacion' => $nuevaFechaFin,
            'precio_actual' => $precioBase,
            'precio_final' => $precioFinal,
            'estado' => $plan->es_gratuito ? 'activa' : 'pendiente',
        ]);

        // Actualizar restaurante si cambió el plan
        if ($nuevoPlan) {
            $suscripcion->restaurante->update([
                'plan_id' => $plan->id,
            ]);
        }

        return $suscripcion;
    }

    /**
     * Cambiar plan de suscripción
     */
    public function cambiarPlan(
        Suscripcion $suscripcion,
        Plan $nuevoPlan,
        bool $prorratear = true
    ): Suscripcion {
        $planAnterior = $suscripcion->plan;

        // Calcular prorrateo si aplica
        $creditoProrrateado = 0;
        if ($prorratear && !$planAnterior->es_gratuito) {
            $diasRestantes = now()->diffInDays($suscripcion->fecha_fin, false);
            $diasTotales = Carbon::parse($suscripcion->fecha_inicio)->diffInDays($suscripcion->fecha_fin);

            if ($diasRestantes > 0 && $diasTotales > 0) {
                $creditoProrrateado = ($suscripcion->precio_final / $diasTotales) * $diasRestantes;
            }
        }

        // Calcular nuevo precio
        $precioBase = $suscripcion->tipo_ciclo === 'anual' ? $nuevoPlan->precio_anual : $nuevoPlan->precio_mensual;
        $precioFinal = max(0, $precioBase - $creditoProrrateado);

        // Registrar en historial
        HistorialSuscripcion::create([
            'suscripcion_id' => $suscripcion->id,
            'plan_anterior_id' => $planAnterior->id,
            'plan_nuevo_id' => $nuevoPlan->id,
            'tipo_cambio' => $nuevoPlan->orden > $planAnterior->orden ? 'upgrade' : 'downgrade',
            'precio_anterior' => $suscripcion->precio_final,
            'precio_nuevo' => $precioFinal,
            'credito_prorrateado' => $creditoProrrateado,
            'fecha_cambio' => now(),
            'notas' => "Crédito prorrateado: RD$ " . number_format($creditoProrrateado, 2),
        ]);

        // Actualizar suscripción
        $suscripcion->update([
            'plan_id' => $nuevoPlan->id,
            'precio_actual' => $precioBase,
            'precio_final' => $precioFinal,
            'estado' => $nuevoPlan->es_gratuito ? 'activa' : 'pendiente',
        ]);

        // Actualizar restaurante
        $suscripcion->restaurante->update([
            'plan_id' => $nuevoPlan->id,
        ]);

        return $suscripcion;
    }

    /**
     * Cancelar suscripción
     */
    public function cancelar(Suscripcion $suscripcion, ?string $motivo = null): Suscripcion
    {
        // Registrar en historial
        HistorialSuscripcion::create([
            'suscripcion_id' => $suscripcion->id,
            'plan_anterior_id' => $suscripcion->plan_id,
            'plan_nuevo_id' => null,
            'tipo_cambio' => 'cancelacion',
            'precio_anterior' => $suscripcion->precio_final,
            'precio_nuevo' => 0,
            'fecha_cambio' => now(),
            'notas' => $motivo,
        ]);

        // Actualizar suscripción
        $suscripcion->update([
            'estado' => 'cancelada',
            'cancelado_at' => now(),
            'motivo_cancelacion' => $motivo,
            'renovacion_automatica' => false,
        ]);

        return $suscripcion;
    }

    /**
     * Suspender suscripción por falta de pago
     */
    public function suspender(Suscripcion $suscripcion): Suscripcion
    {
        $suscripcion->update([
            'estado' => 'suspendida',
        ]);

        $suscripcion->restaurante->update([
            'estado_cuenta' => 'suspendido',
        ]);

        return $suscripcion;
    }

    /**
     * Reactivar suscripción suspendida
     */
    public function reactivar(Suscripcion $suscripcion): Suscripcion
    {
        $suscripcion->update([
            'estado' => 'activa',
        ]);

        $suscripcion->restaurante->update([
            'estado_cuenta' => 'al_dia',
            'dias_mora' => 0,
        ]);

        return $suscripcion;
    }

    /**
     * Procesar suscripciones vencidas (job diario)
     */
    public function procesarVencidas(): array
    {
        $resultados = [
            'avisos_enviados' => 0,
            'vencidas' => 0,
            'suspendidas' => 0,
        ];

        // 1. Enviar avisos de renovación próxima
        $porVencer = Suscripcion::where('estado', 'activa')
            ->whereDate('fecha_fin', '<=', now()->addDays($this->diasAvisoRenovacion))
            ->whereDate('fecha_fin', '>', now())
            ->where('renovacion_automatica', true)
            ->get();

        foreach ($porVencer as $suscripcion) {
            // Enviar notificación
            // $suscripcion->restaurante->propietario->notify(new SuscripcionPorVencer($suscripcion));
            $resultados['avisos_enviados']++;
        }

        // 2. Marcar como vencidas las que pasaron la fecha
        $vencidas = Suscripcion::where('estado', 'activa')
            ->whereDate('fecha_fin', '<', now())
            ->get();

        foreach ($vencidas as $suscripcion) {
            $suscripcion->update(['estado' => 'vencida']);

            $suscripcion->restaurante->update([
                'estado_cuenta' => 'pendiente',
            ]);

            // $suscripcion->restaurante->propietario->notify(new SuscripcionVencida($suscripcion));
            $resultados['vencidas']++;
        }

        // 3. Suspender las que pasaron días de gracia
        $paraSupender = Suscripcion::where('estado', 'vencida')
            ->whereDate('fecha_fin', '<', now()->subDays($this->diasGracia))
            ->get();

        foreach ($paraSupender as $suscripcion) {
            $this->suspender($suscripcion);
            $resultados['suspendidas']++;
        }

        // 4. Actualizar días de mora
        Restaurante::where('estado_cuenta', 'pendiente')
            ->orWhere('estado_cuenta', 'moroso')
            ->each(function ($restaurante) {
                if ($restaurante->suscripcionActiva && $restaurante->suscripcionActiva->fecha_fin) {
                    $diasMora = $restaurante->suscripcionActiva->fecha_fin->diffInDays(now(), false);
                    $diasMora = max(0, $diasMora);

                    $nuevoEstado = $diasMora > $this->diasGracia ? 'moroso' : 'pendiente';

                    $restaurante->update([
                        'dias_mora' => $diasMora,
                        'estado_cuenta' => $nuevoEstado,
                    ]);
                }
            });

        return $resultados;
    }

    /**
     * Obtener resumen de suscripciones
     */
    public function obtenerResumen(): array
    {
        return [
            'activas' => Suscripcion::where('estado', 'activa')->count(),
            'pendientes' => Suscripcion::where('estado', 'pendiente')->count(),
            'vencidas' => Suscripcion::where('estado', 'vencida')->count(),
            'suspendidas' => Suscripcion::where('estado', 'suspendida')->count(),
            'canceladas_mes' => Suscripcion::where('estado', 'cancelada')
                ->whereMonth('cancelado_at', now()->month)
                ->count(),
            'mrr' => $this->calcularMRR(),
            'por_plan' => $this->contarPorPlan(),
        ];
    }

    /**
     * Calcular MRR (Monthly Recurring Revenue)
     */
    public function calcularMRR(): float
    {
        return Suscripcion::where('estado', 'activa')
            ->get()
            ->sum(function ($suscripcion) {
                if ($suscripcion->tipo_ciclo === 'anual') {
                    return $suscripcion->precio_final / 12;
                }
                return $suscripcion->precio_final;
            });
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Calcular precio final con descuentos
     */
    protected function calcularPrecioFinal(
        float $precioBase,
        ?float $descuentoPorcentaje,
        ?float $descuentoMonto
    ): float {
        $precio = $precioBase;

        if ($descuentoPorcentaje) {
            $precio -= ($precio * $descuentoPorcentaje / 100);
        }

        if ($descuentoMonto) {
            $precio -= $descuentoMonto;
        }

        return max(0, $precio);
    }

    /**
     * Contar suscripciones activas por plan
     */
    protected function contarPorPlan(): array
    {
        return Suscripcion::where('estado', 'activa')
            ->select('plan_id', DB::raw('count(*) as total'))
            ->groupBy('plan_id')
            ->with('plan:id,nombre,codigo')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->plan->codigo => $item->total];
            })
            ->toArray();
    }
}
