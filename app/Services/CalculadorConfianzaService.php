<?php

namespace App\Services;

use App\Models\Restaurante;
use App\Models\NivelConfianza;
use App\Models\PuntuacionRestaurante;
use App\Models\HistorialPuntuacion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio de Cálculo de Confianza
 *
 * Calcula y actualiza la puntuación de confianza de los restaurantes
 * basándose en múltiples factores como antigüedad, pagos, calificaciones, etc.
 */
class CalculadorConfianzaService
{
    /**
     * Configuración de pesos para el cálculo
     */
    protected array $pesos = [
        'antiguedad' => 0.15,        // 15% - Tiempo en la plataforma
        'pagos' => 0.25,             // 25% - Historial de pagos
        'pedidos' => 0.15,           // 15% - Volumen y cumplimiento de pedidos
        'calificacion' => 0.20,      // 20% - Calificación de clientes
        'verificaciones' => 0.15,    // 15% - Resultado de verificaciones
        'incidentes' => 0.10,        // 10% - Quejas e incidentes
    ];

    /**
     * Puntos base por factor
     */
    protected array $puntos = [
        'por_mes_antiguedad' => 2,
        'por_pago_puntual' => 5,
        'penalizacion_mora' => -10,
        'por_pedido_exitoso' => 0.5,
        'penalizacion_cancelacion' => -2,
        'por_estrella_calificacion' => 4,
        'por_verificacion_exitosa' => 10,
        'penalizacion_queja' => -5,
        'penalizacion_incidente_grave' => -20,
    ];

    /**
     * Calcular puntuación completa de un restaurante
     */
    public function calcular(Restaurante $restaurante): array
    {
        $puntuaciones = [
            'antiguedad' => $this->calcularPuntuacionAntiguedad($restaurante),
            'pagos' => $this->calcularPuntuacionPagos($restaurante),
            'pedidos' => $this->calcularPuntuacionPedidos($restaurante),
            'calificacion' => $this->calcularPuntuacionCalificacion($restaurante),
            'verificaciones' => $this->calcularPuntuacionVerificaciones($restaurante),
            'incidentes' => $this->calcularPuntuacionIncidentes($restaurante),
        ];

        // Calcular puntuación ponderada
        $puntuacionTotal = 0;
        foreach ($puntuaciones as $factor => $puntuacion) {
            $puntuacionTotal += $puntuacion * $this->pesos[$factor];
        }

        // Normalizar a escala 0-100
        $puntuacionFinal = max(0, min(100, $puntuacionTotal));

        return [
            'puntuacion_total' => round($puntuacionFinal, 2),
            'desglose' => $puntuaciones,
            'pesos' => $this->pesos,
        ];
    }

    /**
     * Calcular y actualizar la puntuación de un restaurante
     */
    public function calcularYActualizar(Restaurante $restaurante): Restaurante
    {
        $resultado = $this->calcular($restaurante);
        $puntuacionAnterior = $restaurante->puntuacion_confianza;
        $nivelAnterior = $restaurante->nivel_confianza_id;

        // Actualizar puntuación
        $restaurante->puntuacion_confianza = $resultado['puntuacion_total'];
        $restaurante->ultima_evaluacion_confianza_at = now();

        // Determinar nuevo nivel
        $nuevoNivel = NivelConfianza::obtenerPorPuntuacion($resultado['puntuacion_total']);

        if ($nuevoNivel) {
            $restaurante->nivel_confianza_id = $nuevoNivel->id;

            // Actualizar próxima verificación según el nivel
            $restaurante->proxima_verificacion = now()->addDays($nuevoNivel->dias_verificacion);
        }

        $restaurante->save();

        // Registrar en historial si hubo cambio significativo
        if (abs($puntuacionAnterior - $resultado['puntuacion_total']) >= 1 ||
            $nivelAnterior !== $restaurante->nivel_confianza_id) {

            $this->registrarHistorial($restaurante, $resultado, $puntuacionAnterior, $nivelAnterior);
        }

        // Guardar puntuaciones detalladas
        $this->guardarPuntuacionesDetalladas($restaurante, $resultado);

        return $restaurante;
    }

    /**
     * Recalcular confianza de todos los restaurantes
     */
    public function recalcularTodos(): array
    {
        $resultados = [
            'procesados' => 0,
            'actualizados' => 0,
            'errores' => [],
        ];

        $restaurantes = Restaurante::where('activo', true)
            ->where('estado_onboarding', 'completado')
            ->cursor();

        foreach ($restaurantes as $restaurante) {
            try {
                $puntuacionAnterior = $restaurante->puntuacion_confianza;
                $this->calcularYActualizar($restaurante);
                $resultados['procesados']++;

                if ($restaurante->puntuacion_confianza !== $puntuacionAnterior) {
                    $resultados['actualizados']++;
                }
            } catch (\Exception $e) {
                $resultados['errores'][] = [
                    'restaurante_id' => $restaurante->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $resultados;
    }

    /**
     * Aplicar ajuste manual a la puntuación
     */
    public function aplicarAjuste(
        Restaurante $restaurante,
        float $ajuste,
        string $motivo,
        int $aplicadoPor
    ): Restaurante {
        $puntuacionAnterior = $restaurante->puntuacion_confianza;
        $nuevaPuntuacion = max(0, min(100, $puntuacionAnterior + $ajuste));

        $restaurante->puntuacion_confianza = $nuevaPuntuacion;
        $restaurante->ultima_evaluacion_confianza_at = now();

        // Actualizar nivel si es necesario
        $nuevoNivel = NivelConfianza::obtenerPorPuntuacion($nuevaPuntuacion);
        if ($nuevoNivel) {
            $restaurante->nivel_confianza_id = $nuevoNivel->id;
        }

        $restaurante->save();

        // Registrar ajuste en historial
        HistorialPuntuacion::create([
            'restaurante_id' => $restaurante->id,
            'puntuacion_anterior' => $puntuacionAnterior,
            'puntuacion_nueva' => $nuevaPuntuacion,
            'tipo_cambio' => 'ajuste_manual',
            'motivo' => $motivo,
            'aplicado_por' => $aplicadoPor,
            'fecha_cambio' => now(),
        ]);

        return $restaurante;
    }

    // =========================================================================
    // CÁLCULOS POR FACTOR
    // =========================================================================

    /**
     * Calcular puntuación por antigüedad (0-100)
     */
    protected function calcularPuntuacionAntiguedad(Restaurante $restaurante): float
    {
        $mesesAntiguedad = $restaurante->created_at->diffInMonths(now());

        // Máximo 24 meses para llegar a 100
        $puntos = min($mesesAntiguedad * $this->puntos['por_mes_antiguedad'], 48);

        // Normalizar a 100
        return min(100, ($puntos / 48) * 100);
    }

    /**
     * Calcular puntuación por historial de pagos (0-100)
     */
    protected function calcularPuntuacionPagos(Restaurante $restaurante): float
    {
        // Si es plan gratuito, dar puntuación neutral
        if ($restaurante->plan && $restaurante->plan->es_gratuito) {
            return 70;
        }

        $suscripciones = $restaurante->suscripciones()
            ->where('created_at', '>=', now()->subYear())
            ->get();

        if ($suscripciones->isEmpty()) {
            return 50; // Neutral si no hay historial
        }

        $puntos = 50; // Base

        foreach ($suscripciones as $suscripcion) {
            // Pagos puntuales
            if ($suscripcion->estado === 'activa') {
                $puntos += $this->puntos['por_pago_puntual'];
            }

            // Penalización por mora
            if ($suscripcion->estado === 'vencida' || $suscripcion->estado === 'suspendida') {
                $puntos += $this->puntos['penalizacion_mora'];
            }
        }

        // Considerar mora actual
        if ($restaurante->dias_mora > 0) {
            $puntos += ($restaurante->dias_mora / 7) * $this->puntos['penalizacion_mora'];
        }

        return max(0, min(100, $puntos));
    }

    /**
     * Calcular puntuación por pedidos (0-100)
     */
    protected function calcularPuntuacionPedidos(Restaurante $restaurante): float
    {
        $pedidosUltimosMeses = $restaurante->pedidos()
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        if ($pedidosUltimosMeses->isEmpty()) {
            return 50; // Neutral si no hay pedidos
        }

        $puntos = 50;

        foreach ($pedidosUltimosMeses as $pedido) {
            if (in_array($pedido->estado, ['entregado', 'completado'])) {
                $puntos += $this->puntos['por_pedido_exitoso'];
            } elseif (in_array($pedido->estado, ['cancelado_restaurante', 'rechazado'])) {
                $puntos += $this->puntos['penalizacion_cancelacion'];
            }
        }

        return max(0, min(100, $puntos));
    }

    /**
     * Calcular puntuación por calificación de clientes (0-100)
     */
    protected function calcularPuntuacionCalificacion(Restaurante $restaurante): float
    {
        $calificacion = $restaurante->calificacion ?? 0;

        if ($calificacion === 0 || $restaurante->total_resenas < 5) {
            return 50; // Neutral si no hay suficientes reseñas
        }

        // Escala directa: 5 estrellas = 100, 1 estrella = 20
        return max(20, min(100, $calificacion * 20));
    }

    /**
     * Calcular puntuación por verificaciones (0-100)
     */
    protected function calcularPuntuacionVerificaciones(Restaurante $restaurante): float
    {
        $verificaciones = $restaurante->verificaciones()
            ->where('created_at', '>=', now()->subYear())
            ->get();

        if ($verificaciones->isEmpty()) {
            // Si nunca ha sido verificado, dar puntuación baja
            return $restaurante->verificado ? 60 : 30;
        }

        $puntos = 50;

        foreach ($verificaciones as $verificacion) {
            if ($verificacion->resultado === 'aprobado') {
                $puntos += $this->puntos['por_verificacion_exitosa'];
            } elseif ($verificacion->resultado === 'observaciones') {
                $puntos += 5;
            } elseif ($verificacion->resultado === 'rechazado') {
                $puntos -= 15;
            }
        }

        return max(0, min(100, $puntos));
    }

    /**
     * Calcular puntuación por incidentes (0-100)
     */
    protected function calcularPuntuacionIncidentes(Restaurante $restaurante): float
    {
        // Comenzar con puntuación perfecta y restar por incidentes
        $puntos = 100;

        // Contar quejas validadas
        $quejas = $restaurante->ticketsSoporte()
            ->where('tipo', 'queja')
            ->where('estado', 'resuelto')
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        $puntos += $quejas * $this->puntos['penalizacion_queja'];

        // Verificar si está suspendido
        if ($restaurante->nivelConfianza && $restaurante->nivelConfianza->codigo === 'suspendido') {
            $puntos += $this->puntos['penalizacion_incidente_grave'];
        }

        return max(0, min(100, $puntos));
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Registrar cambio en historial
     */
    protected function registrarHistorial(
        Restaurante $restaurante,
        array $resultado,
        float $puntuacionAnterior,
        ?int $nivelAnterior
    ): void {
        HistorialPuntuacion::create([
            'restaurante_id' => $restaurante->id,
            'puntuacion_anterior' => $puntuacionAnterior,
            'puntuacion_nueva' => $resultado['puntuacion_total'],
            'nivel_anterior_id' => $nivelAnterior,
            'nivel_nuevo_id' => $restaurante->nivel_confianza_id,
            'tipo_cambio' => 'recalculo_automatico',
            'desglose' => $resultado['desglose'],
            'fecha_cambio' => now(),
        ]);
    }

    /**
     * Guardar puntuaciones detalladas
     */
    protected function guardarPuntuacionesDetalladas(Restaurante $restaurante, array $resultado): void
    {
        foreach ($resultado['desglose'] as $criterio => $valor) {
            PuntuacionRestaurante::updateOrCreate(
                [
                    'restaurante_id' => $restaurante->id,
                    'criterio' => $criterio,
                ],
                [
                    'valor' => $valor,
                    'peso' => $this->pesos[$criterio],
                    'valor_ponderado' => $valor * $this->pesos[$criterio],
                    'ultima_actualizacion' => now(),
                ]
            );
        }
    }

    /**
     * Obtener tendencia de confianza (últimos 6 meses)
     */
    public function obtenerTendencia(Restaurante $restaurante): array
    {
        $historial = HistorialPuntuacion::where('restaurante_id', $restaurante->id)
            ->where('fecha_cambio', '>=', now()->subMonths(6))
            ->orderBy('fecha_cambio')
            ->get();

        $tendencia = [];
        foreach ($historial as $registro) {
            $tendencia[] = [
                'fecha' => $registro->fecha_cambio->format('Y-m-d'),
                'puntuacion' => $registro->puntuacion_nueva,
            ];
        }

        // Agregar puntuación actual si no está en el historial
        $tendencia[] = [
            'fecha' => now()->format('Y-m-d'),
            'puntuacion' => $restaurante->puntuacion_confianza,
        ];

        return $tendencia;
    }
}
