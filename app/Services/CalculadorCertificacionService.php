<?php

namespace App\Services;

use App\Models\Restaurante;
use App\Models\Certificacion;
use App\Models\CriterioCertificacion;
use App\Models\EvaluacionCertificacion;
use App\Models\HistorialCertificacion;
use App\Models\Personal;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Cálculo de Certificación
 *
 * Evalúa y asigna certificaciones públicas (A, B, C, D, E) a restaurantes
 * basándose en criterios de calidad, higiene, servicio, etc.
 */
class CalculadorCertificacionService
{
    /**
     * Realizar evaluación completa de certificación
     */
    public function evaluar(
        Restaurante $restaurante,
        array $puntuacionesCriterios,
        Personal $evaluador,
        ?string $observaciones = null
    ): EvaluacionCertificacion {
        // Obtener criterios activos
        $criterios = CriterioCertificacion::where('activo', true)->get();

        // Calcular puntuación ponderada
        $puntuacionTotal = 0;
        $pesoTotal = 0;
        $detalleEvaluacion = [];

        foreach ($criterios as $criterio) {
            $puntuacion = $puntuacionesCriterios[$criterio->codigo] ?? 0;
            $puntuacionPonderada = ($puntuacion / 100) * $criterio->peso;

            $puntuacionTotal += $puntuacionPonderada;
            $pesoTotal += $criterio->peso;

            $detalleEvaluacion[$criterio->codigo] = [
                'nombre' => $criterio->nombre,
                'puntuacion' => $puntuacion,
                'peso' => $criterio->peso,
                'puntuacion_ponderada' => $puntuacionPonderada,
            ];
        }

        // Normalizar si los pesos no suman 100
        if ($pesoTotal > 0 && $pesoTotal !== 100) {
            $puntuacionTotal = ($puntuacionTotal / $pesoTotal) * 100;
        }

        // Crear evaluación
        $evaluacion = EvaluacionCertificacion::create([
            'restaurante_id' => $restaurante->id,
            'evaluador_id' => $evaluador->id,
            'puntuacion_total' => round($puntuacionTotal, 2),
            'puntuaciones_criterios' => $detalleEvaluacion,
            'observaciones' => $observaciones,
            'fecha_evaluacion' => now(),
            'estado' => 'completada',
        ]);

        // Actualizar certificación del restaurante
        $this->actualizarCertificacion($restaurante, $puntuacionTotal, $evaluacion);

        return $evaluacion;
    }

    /**
     * Calcular puntuación automática basada en datos del sistema
     */
    public function calcularAutomatico(Restaurante $restaurante): array
    {
        $puntuaciones = [];

        // Calidad de comida - basado en reseñas específicas de comida
        $puntuaciones['calidad_comida'] = $this->calcularPuntuacionCalidad($restaurante);

        // Tiempo de preparación - basado en datos de pedidos
        $puntuaciones['tiempo_preparacion'] = $this->calcularPuntuacionTiempo($restaurante);

        // Higiene - requiere verificación manual, usar último valor o default
        $puntuaciones['higiene'] = $this->obtenerUltimaPuntuacionManual($restaurante, 'higiene') ?? 70;

        // Servicio al cliente - basado en reseñas y resolución de quejas
        $puntuaciones['servicio'] = $this->calcularPuntuacionServicio($restaurante);

        // Información actualizada - verificar datos del perfil
        $puntuaciones['informacion'] = $this->calcularPuntuacionInformacion($restaurante);

        // Cumplimiento de pedidos - basado en pedidos completados vs cancelados
        $puntuaciones['cumplimiento'] = $this->calcularPuntuacionCumplimiento($restaurante);

        return $puntuaciones;
    }

    /**
     * Actualizar certificación del restaurante
     */
    public function actualizarCertificacion(
        Restaurante $restaurante,
        float $puntuacion,
        ?EvaluacionCertificacion $evaluacion = null
    ): void {
        $certificacionAnterior = $restaurante->certificacion_id;
        $nuevaCertificacion = Certificacion::obtenerPorPuntuacion($puntuacion);

        if (!$nuevaCertificacion) {
            return;
        }

        // Registrar historial si cambia la certificación
        if ($certificacionAnterior !== $nuevaCertificacion->id) {
            HistorialCertificacion::create([
                'restaurante_id' => $restaurante->id,
                'certificacion_anterior_id' => $certificacionAnterior,
                'certificacion_nueva_id' => $nuevaCertificacion->id,
                'puntuacion' => $puntuacion,
                'evaluacion_id' => $evaluacion?->id,
                'motivo' => $evaluacion ? 'Evaluación de certificación' : 'Recálculo automático',
                'fecha_cambio' => now(),
            ]);
        }

        // Actualizar restaurante
        $restaurante->update([
            'puntuacion_certificacion' => $puntuacion,
            'certificacion_id' => $nuevaCertificacion->id,
            'certificacion_otorgada_at' => now(),
            'certificacion_vence_at' => $nuevaCertificacion->calcularFechaVencimiento(),
        ]);
    }

    /**
     * Revocar certificación
     */
    public function revocar(
        Restaurante $restaurante,
        string $motivo,
        Personal $revocadoPor
    ): void {
        $certificacionAnterior = $restaurante->certificacion_id;

        // Registrar en historial
        HistorialCertificacion::create([
            'restaurante_id' => $restaurante->id,
            'certificacion_anterior_id' => $certificacionAnterior,
            'certificacion_nueva_id' => null,
            'puntuacion' => 0,
            'motivo' => "Revocación: {$motivo}",
            'revocado_por' => $revocadoPor->id,
            'fecha_cambio' => now(),
        ]);

        // Actualizar restaurante
        $restaurante->update([
            'certificacion_id' => null,
            'puntuacion_certificacion' => 0,
            'certificacion_otorgada_at' => null,
            'certificacion_vence_at' => null,
        ]);
    }

    /**
     * Verificar certificaciones por vencer
     */
    public function obtenerPorVencer(int $dias = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Restaurante::whereNotNull('certificacion_id')
            ->whereNotNull('certificacion_vence_at')
            ->whereDate('certificacion_vence_at', '<=', now()->addDays($dias))
            ->whereDate('certificacion_vence_at', '>', now())
            ->with(['certificacion', 'municipio'])
            ->get();
    }

    /**
     * Procesar certificaciones vencidas
     */
    public function procesarVencidas(): array
    {
        $vencidas = Restaurante::whereNotNull('certificacion_id')
            ->whereDate('certificacion_vence_at', '<', now())
            ->get();

        $procesados = [];

        foreach ($vencidas as $restaurante) {
            // Degradar a certificación C si tenía mejor
            $certificacionC = Certificacion::where('codigo', 'C')->first();

            if ($restaurante->certificacion &&
                $restaurante->certificacion->orden < ($certificacionC->orden ?? 3)) {

                HistorialCertificacion::create([
                    'restaurante_id' => $restaurante->id,
                    'certificacion_anterior_id' => $restaurante->certificacion_id,
                    'certificacion_nueva_id' => $certificacionC->id,
                    'puntuacion' => $restaurante->puntuacion_certificacion,
                    'motivo' => 'Certificación vencida - degradado automático',
                    'fecha_cambio' => now(),
                ]);

                $restaurante->update([
                    'certificacion_id' => $certificacionC->id,
                    'certificacion_vence_at' => $certificacionC->calcularFechaVencimiento(),
                ]);

                $procesados[] = $restaurante->id;
            }
        }

        return $procesados;
    }

    // =========================================================================
    // CÁLCULOS AUTOMÁTICOS
    // =========================================================================

    /**
     * Calcular puntuación de calidad basada en reseñas
     */
    protected function calcularPuntuacionCalidad(Restaurante $restaurante): float
    {
        $calificacion = $restaurante->calificacion ?? 0;

        if ($calificacion === 0 || $restaurante->total_resenas < 5) {
            return 60; // Neutral si no hay suficientes datos
        }

        // Escala: 5 estrellas = 100, 1 estrella = 20
        return max(20, min(100, $calificacion * 20));
    }

    /**
     * Calcular puntuación de tiempo de preparación
     */
    protected function calcularPuntuacionTiempo(Restaurante $restaurante): float
    {
        $pedidos = $restaurante->pedidos()
            ->whereNotNull('tiempo_preparacion_real')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        if ($pedidos->isEmpty()) {
            return 70; // Neutral
        }

        $cumplidos = 0;
        $total = $pedidos->count();

        foreach ($pedidos as $pedido) {
            $tiempoEstimado = $restaurante->tiempo_preparacion ?? 30;
            $tiempoReal = $pedido->tiempo_preparacion_real;

            // Considerar cumplido si está dentro de 10 minutos del estimado
            if ($tiempoReal <= $tiempoEstimado + 10) {
                $cumplidos++;
            }
        }

        return ($cumplidos / $total) * 100;
    }

    /**
     * Calcular puntuación de servicio al cliente
     */
    protected function calcularPuntuacionServicio(Restaurante $restaurante): float
    {
        // Basado en resolución de tickets de soporte
        $ticketsResueltos = $restaurante->ticketsSoporte()
            ->where('estado', 'resuelto')
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        $ticketsTotal = $restaurante->ticketsSoporte()
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        if ($ticketsTotal === 0) {
            return 80; // Bueno si no hay quejas
        }

        $tasaResolucion = ($ticketsResueltos / $ticketsTotal) * 100;

        // Combinar con calificación
        $calificacion = $restaurante->calificacion ?? 3;
        $puntuacionCalificacion = $calificacion * 20;

        return ($tasaResolucion * 0.5) + ($puntuacionCalificacion * 0.5);
    }

    /**
     * Calcular puntuación de información actualizada
     */
    protected function calcularPuntuacionInformacion(Restaurante $restaurante): float
    {
        $puntos = 100;

        // Verificar campos completos
        if (empty($restaurante->descripcion)) $puntos -= 10;
        if (empty($restaurante->horarios_atencion)) $puntos -= 15;
        if (empty($restaurante->telefono) && empty($restaurante->whatsapp)) $puntos -= 15;
        if (empty($restaurante->direccion)) $puntos -= 10;
        if (empty($restaurante->tipos_cocina)) $puntos -= 5;

        // Verificar menú actualizado
        $ultimoPlato = $restaurante->platos()->latest('updated_at')->first();
        if (!$ultimoPlato || $ultimoPlato->updated_at->diffInMonths(now()) > 3) {
            $puntos -= 20;
        }

        // Verificar si tiene fotos
        if (empty($restaurante->logo)) $puntos -= 10;
        if (empty($restaurante->imagen_portada)) $puntos -= 5;

        return max(0, $puntos);
    }

    /**
     * Calcular puntuación de cumplimiento de pedidos
     */
    protected function calcularPuntuacionCumplimiento(Restaurante $restaurante): float
    {
        $pedidos = $restaurante->pedidos()
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        if ($pedidos->isEmpty()) {
            return 70; // Neutral
        }

        $completados = $pedidos->whereIn('estado', ['entregado', 'completado', 'recogido'])->count();
        $total = $pedidos->count();

        return ($completados / $total) * 100;
    }

    /**
     * Obtener última puntuación manual de un criterio
     */
    protected function obtenerUltimaPuntuacionManual(Restaurante $restaurante, string $criterio): ?float
    {
        $ultimaEvaluacion = $restaurante->evaluacionesCertificacion()
            ->where('estado', 'completada')
            ->latest('fecha_evaluacion')
            ->first();

        if (!$ultimaEvaluacion || !$ultimaEvaluacion->puntuaciones_criterios) {
            return null;
        }

        return $ultimaEvaluacion->puntuaciones_criterios[$criterio]['puntuacion'] ?? null;
    }
}
