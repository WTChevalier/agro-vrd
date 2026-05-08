<?php

namespace App\Services;

use App\Models\Restaurante;
use App\Models\MaterialPromocional;
use App\Models\EntregaMaterial;
use App\Models\MovimientoInventario;
use App\Models\KitPlan;
use App\Models\Personal;
use App\Models\PlacaCertificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de Gestión de Materiales Promocionales
 *
 * Maneja el inventario, kits y entregas de materiales a restaurantes
 */
class GestorMaterialesService
{
    /**
     * Registrar entrega de kit completo a restaurante
     */
    public function entregarKit(
        Restaurante $restaurante,
        Personal $entregadoPor,
        ?string $observaciones = null
    ): array {
        $plan = $restaurante->plan;

        if (!$plan) {
            throw new \Exception('El restaurante no tiene un plan asignado');
        }

        // Obtener materiales del kit
        $kitItems = KitPlan::where('plan_id', $plan->id)
            ->where('es_inicial', true)
            ->with('material')
            ->get();

        if ($kitItems->isEmpty()) {
            throw new \Exception('El plan no tiene kit de materiales configurado');
        }

        $entregas = [];
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($kitItems as $item) {
                $material = $item->material;

                // Verificar stock
                if ($material->stock_actual < $item->cantidad) {
                    $errores[] = "Stock insuficiente de {$material->nombre}";
                    continue;
                }

                // Crear entrega
                $entrega = EntregaMaterial::create([
                    'restaurante_id' => $restaurante->id,
                    'material_id' => $material->id,
                    'cantidad' => $item->cantidad,
                    'tipo_entrega' => 'kit_inicial',
                    'entregado_por' => $entregadoPor->id,
                    'recibido_por' => null, // Se actualiza con firma
                    'fecha_entrega' => now(),
                    'estado' => 'pendiente',
                    'observaciones' => $observaciones,
                ]);

                // Descontar inventario
                $this->registrarMovimiento(
                    $material,
                    -$item->cantidad,
                    'salida_entrega',
                    "Entrega kit inicial a {$restaurante->nombre}",
                    $entrega->id
                );

                $entregas[] = $entrega;
            }

            DB::commit();

            return [
                'exito' => count($errores) === 0,
                'entregas' => $entregas,
                'errores' => $errores,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Entregar material individual
     */
    public function entregarMaterial(
        Restaurante $restaurante,
        MaterialPromocional $material,
        int $cantidad,
        Personal $entregadoPor,
        string $tipoEntrega = 'individual',
        ?string $observaciones = null
    ): EntregaMaterial {
        // Verificar stock
        if ($material->stock_actual < $cantidad) {
            throw new \Exception("Stock insuficiente. Disponible: {$material->stock_actual}");
        }

        // Verificar si el restaurante puede recibir este material
        if ($material->requiere_certificacion && $material->certificacion_minima_id) {
            if (!$restaurante->certificacion_id ||
                $restaurante->certificacion->orden > $material->certificacionMinima->orden) {
                throw new \Exception("El restaurante no tiene la certificación requerida para este material");
            }
        }

        DB::beginTransaction();

        try {
            // Crear entrega
            $entrega = EntregaMaterial::create([
                'restaurante_id' => $restaurante->id,
                'material_id' => $material->id,
                'cantidad' => $cantidad,
                'tipo_entrega' => $tipoEntrega,
                'entregado_por' => $entregadoPor->id,
                'fecha_entrega' => now(),
                'estado' => 'pendiente',
                'observaciones' => $observaciones,
            ]);

            // Descontar inventario
            $this->registrarMovimiento(
                $material,
                -$cantidad,
                'salida_entrega',
                "Entrega a {$restaurante->nombre}",
                $entrega->id
            );

            DB::commit();

            return $entrega;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Confirmar recepción de entrega
     */
    public function confirmarRecepcion(
        EntregaMaterial $entrega,
        string $nombreRecibido,
        ?string $firmaBase64 = null
    ): EntregaMaterial {
        $entrega->update([
            'estado' => 'entregado',
            'recibido_por' => $nombreRecibido,
            'firma_recepcion' => $firmaBase64,
            'fecha_confirmacion' => now(),
        ]);

        return $entrega;
    }

    /**
     * Emitir placa de certificación
     */
    public function emitirPlaca(
        Restaurante $restaurante,
        Personal $emitidoPor
    ): PlacaCertificacion {
        if (!$restaurante->certificacion_id) {
            throw new \Exception('El restaurante no tiene certificación asignada');
        }

        $certificacion = $restaurante->certificacion;

        // Verificar si puede recibir placa según certificación
        if (in_array($certificacion->codigo, ['D', 'E'])) {
            throw new \Exception('Las certificaciones D y E no incluyen placa');
        }

        // Generar código único
        $codigoPlaca = $this->generarCodigoPlaca();
        $codigoVerificacion = strtoupper(Str::random(10));

        // Revocar placas anteriores
        PlacaCertificacion::where('restaurante_id', $restaurante->id)
            ->where('estado', 'activa')
            ->update(['estado' => 'reemplazada']);

        // Crear nueva placa
        $placa = PlacaCertificacion::create([
            'restaurante_id' => $restaurante->id,
            'certificacion_id' => $certificacion->id,
            'codigo_placa' => $codigoPlaca,
            'codigo_verificacion' => $codigoVerificacion,
            'fecha_emision' => now(),
            'fecha_vencimiento' => $certificacion->calcularFechaVencimiento(),
            'estado' => 'activa',
            'emitido_por' => $emitidoPor->id,
        ]);

        // Generar QR
        app(GeneradorQrService::class)->generarParaPlaca($placa);

        return $placa;
    }

    /**
     * Registrar entrada de inventario
     */
    public function registrarEntrada(
        MaterialPromocional $material,
        int $cantidad,
        string $motivo,
        ?float $costoUnitario = null,
        ?string $proveedor = null,
        ?string $numeroFactura = null
    ): MovimientoInventario {
        return $this->registrarMovimiento(
            $material,
            $cantidad,
            'entrada_compra',
            $motivo,
            null,
            [
                'costo_unitario' => $costoUnitario ?? $material->costo_unitario,
                'proveedor' => $proveedor,
                'numero_factura' => $numeroFactura,
            ]
        );
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste(
        MaterialPromocional $material,
        int $cantidadAjuste,
        string $motivo,
        Personal $ajustadoPor
    ): MovimientoInventario {
        $tipo = $cantidadAjuste > 0 ? 'ajuste_positivo' : 'ajuste_negativo';

        return $this->registrarMovimiento(
            $material,
            $cantidadAjuste,
            $tipo,
            $motivo,
            null,
            ['ajustado_por' => $ajustadoPor->id]
        );
    }

    /**
     * Obtener materiales con stock bajo
     */
    public function obtenerStockBajo(): \Illuminate\Database\Eloquent\Collection
    {
        return MaterialPromocional::where('activo', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual')
            ->get();
    }

    /**
     * Obtener resumen de inventario
     */
    public function obtenerResumenInventario(): array
    {
        $materiales = MaterialPromocional::where('activo', true)->get();

        return [
            'total_items' => $materiales->count(),
            'valor_total' => $materiales->sum(fn($m) => $m->stock_actual * $m->costo_unitario),
            'stock_bajo' => $materiales->filter(fn($m) => $m->stock_actual <= $m->stock_minimo)->count(),
            'sin_stock' => $materiales->filter(fn($m) => $m->stock_actual === 0)->count(),
            'por_tipo' => $materiales->groupBy('tipo')->map->count()->toArray(),
        ];
    }

    /**
     * Obtener historial de entregas de un restaurante
     */
    public function obtenerHistorialEntregas(Restaurante $restaurante): \Illuminate\Database\Eloquent\Collection
    {
        return EntregaMaterial::where('restaurante_id', $restaurante->id)
            ->with(['material', 'entregador'])
            ->orderBy('fecha_entrega', 'desc')
            ->get();
    }

    /**
     * Verificar si restaurante puede recibir renovación de materiales
     */
    public function puedeRecibirRenovacion(Restaurante $restaurante): array
    {
        $plan = $restaurante->plan;

        if (!$plan) {
            return ['puede' => false, 'motivo' => 'Sin plan asignado'];
        }

        // Verificar última entrega del kit
        $ultimaEntrega = EntregaMaterial::where('restaurante_id', $restaurante->id)
            ->where('tipo_entrega', 'kit_inicial')
            ->latest('fecha_entrega')
            ->first();

        if (!$ultimaEntrega) {
            return ['puede' => true, 'motivo' => 'Nunca ha recibido kit'];
        }

        // Verificar tiempo desde última entrega
        $mesesDesdeUltima = $ultimaEntrega->fecha_entrega->diffInMonths(now());

        $kitConfig = KitPlan::where('plan_id', $plan->id)
            ->where('es_renovable', true)
            ->first();

        if (!$kitConfig) {
            return ['puede' => false, 'motivo' => 'El plan no incluye renovación de materiales'];
        }

        if ($mesesDesdeUltima < $kitConfig->renovacion_cada_meses) {
            $mesesRestantes = $kitConfig->renovacion_cada_meses - $mesesDesdeUltima;
            return [
                'puede' => false,
                'motivo' => "Próxima renovación en {$mesesRestantes} meses",
                'fecha_proxima' => $ultimaEntrega->fecha_entrega->addMonths($kitConfig->renovacion_cada_meses),
            ];
        }

        return ['puede' => true, 'motivo' => 'Elegible para renovación'];
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Registrar movimiento de inventario
     */
    protected function registrarMovimiento(
        MaterialPromocional $material,
        int $cantidad,
        string $tipo,
        string $descripcion,
        ?int $entregaId = null,
        array $datosAdicionales = []
    ): MovimientoInventario {
        $stockAnterior = $material->stock_actual;
        $stockNuevo = $stockAnterior + $cantidad;

        // Actualizar stock
        $material->update(['stock_actual' => max(0, $stockNuevo)]);

        // Registrar movimiento
        return MovimientoInventario::create([
            'material_id' => $material->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => max(0, $stockNuevo),
            'descripcion' => $descripcion,
            'entrega_id' => $entregaId,
            'datos_adicionales' => !empty($datosAdicionales) ? $datosAdicionales : null,
            'fecha_movimiento' => now(),
        ]);
    }

    /**
     * Generar código único de placa
     */
    protected function generarCodigoPlaca(): string
    {
        $year = now()->format('Y');
        $numero = PlacaCertificacion::whereYear('created_at', $year)->count() + 1;

        return sprintf('SR-%s-%05d', $year, $numero);
    }
}
