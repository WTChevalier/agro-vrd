<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\EstadoPedido;
use App\Models\HistorialEstadoPedido;
use App\Models\Carrito;
use App\Models\Usuario;
use App\Models\DireccionUsuario;
use App\Events\PedidoCreado;
use App\Events\PedidoCancelado;
use App\Events\EstadoPedidoCambiado;
use App\Notifications\PedidoConfirmado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de Pedidos
 *
 * Maneja la lógica de negocio para la gestión de pedidos.
 */
class PedidoService
{
    /**
     * Crear un nuevo pedido desde el carrito.
     */
    public function crearPedido(array $datos): Pedido
    {
        return DB::transaction(function () use ($datos) {
            $carrito = $datos['carrito'];
            $usuario = Usuario::find($datos['usuario_id']);

            // Calcular totales
            $totales = $carrito->calcularTotales();
            $costoDelivery = 0;

            // Calcular costo de delivery si aplica
            if ($datos['tipo'] === 'delivery' && $datos['direccion']) {
                $cobertura = $carrito->restaurante->verificarCoberturaDelivery(
                    $datos['direccion']->latitud,
                    $datos['direccion']->longitud
                );
                $costoDelivery = $cobertura['costo_delivery'] ?? 0;
            }

            // Calcular descuento por puntos si aplica
            $descuentoPuntos = 0;
            if (!empty($datos['usar_puntos']) && $usuario->puntos_lealtad > 0) {
                $descuentoPuntos = min(
                    $usuario->puntos_lealtad * 0.01, // 1 punto = RD$0.01
                    $totales['subtotal'] * 0.2 // Máximo 20% de descuento
                );
            }

            // Generar número de pedido único
            $numeroPedido = $this->generarNumeroPedido();

            // Obtener estado inicial
            $estadoPendiente = EstadoPedido::where('codigo', 'pendiente')->first();

            // Crear el pedido
            $pedido = Pedido::create([
                'numero_pedido' => $numeroPedido,
                'usuario_id' => $datos['usuario_id'],
                'restaurante_id' => $carrito->restaurante_id,
                'estado_id' => $estadoPendiente->id,
                'direccion_entrega_id' => $datos['direccion']?->id,
                'cupon_id' => $carrito->cupon_id,
                'tipo' => $datos['tipo'],
                'subtotal' => $totales['subtotal'],
                'descuento' => $totales['descuento'] + $descuentoPuntos,
                'costo_delivery' => $costoDelivery,
                'propina' => $datos['propina'] ?? 0,
                'total' => $totales['subtotal'] - $totales['descuento'] - $descuentoPuntos + $costoDelivery + ($datos['propina'] ?? 0),
                'metodo_pago' => $datos['metodo_pago'],
                'instrucciones' => $datos['instrucciones'] ?? null,
                'tiempo_estimado_preparacion' => $carrito->restaurante->tiempo_preparacion_promedio,
                'tiempo_estimado_entrega' => $datos['tipo'] === 'delivery'
                    ? $this->calcularTiempoEntrega($carrito->restaurante, $datos['direccion'])
                    : null,
            ]);

            // Crear items del pedido
            foreach ($carrito->items as $item) {
                ItemPedido::create([
                    'pedido_id' => $pedido->id,
                    'tipo_producto' => $item->tipo_producto,
                    'producto_id' => $item->producto_id,
                    'nombre_producto' => $item->plato?->nombre ?? $item->combo?->nombre,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'precio_opciones' => $item->precio_opciones,
                    'subtotal' => $item->subtotal,
                    'instrucciones_especiales' => $item->instrucciones_especiales,
                    'opciones_seleccionadas' => $item->opciones_json,
                ]);
            }

            // Registrar en historial de estados
            HistorialEstadoPedido::create([
                'pedido_id' => $pedido->id,
                'estado_id' => $estadoPendiente->id,
                'usuario_id' => $datos['usuario_id'],
                'notas' => 'Pedido creado',
            ]);

            // Descontar puntos si se usaron
            if ($descuentoPuntos > 0) {
                $puntosUsados = (int) ($descuentoPuntos / 0.01);
                $usuario->usarPuntosLealtad($puntosUsados, 'Descuento en pedido ' . $numeroPedido, $pedido);
            }

            // Incrementar uso de cupón si aplica
            if ($carrito->cupon) {
                $carrito->cupon->incrementarUso($usuario);
            }

            // Disparar eventos
            event(new PedidoCreado($pedido));

            // Notificar al usuario
            $usuario->notify(new PedidoConfirmado($pedido));

            return $pedido;
        });
    }

    /**
     * Cancelar un pedido.
     */
    public function cancelarPedido(Pedido $pedido, string $motivo, Usuario $usuario): void
    {
        DB::transaction(function () use ($pedido, $motivo, $usuario) {
            $estadoCancelado = EstadoPedido::where('codigo', 'cancelado')->first();

            $pedido->update([
                'estado_id' => $estadoCancelado->id,
                'motivo_cancelacion' => $motivo,
                'cancelado_por' => $usuario->id,
                'cancelado_at' => now(),
            ]);

            // Registrar en historial
            HistorialEstadoPedido::create([
                'pedido_id' => $pedido->id,
                'estado_id' => $estadoCancelado->id,
                'usuario_id' => $usuario->id,
                'notas' => "Cancelado: {$motivo}",
            ]);

            // Reembolsar si ya se pagó
            if ($pedido->metodo_pago !== 'efectivo' && $pedido->pagado) {
                // Reembolsar a billetera
                $pedido->usuario->agregarSaldoBilletera(
                    $pedido->total,
                    'Reembolso por cancelación de pedido ' . $pedido->numero_pedido,
                    $pedido
                );
            }

            // Restaurar cupón si se usó
            if ($pedido->cupon) {
                $pedido->cupon->decrementarUso($pedido->usuario);
            }

            // Restaurar puntos si se usaron
            // (esto requeriría tracking adicional de puntos usados por pedido)

            // Disparar evento
            event(new PedidoCancelado($pedido));
        });
    }

    /**
     * Cambiar el estado de un pedido.
     */
    public function cambiarEstado(Pedido $pedido, string $codigoEstado, ?Usuario $usuario = null, ?string $notas = null): void
    {
        $nuevoEstado = EstadoPedido::where('codigo', $codigoEstado)->firstOrFail();

        DB::transaction(function () use ($pedido, $nuevoEstado, $usuario, $notas) {
            $estadoAnterior = $pedido->estado;

            $pedido->update(['estado_id' => $nuevoEstado->id]);

            // Registrar en historial
            HistorialEstadoPedido::create([
                'pedido_id' => $pedido->id,
                'estado_id' => $nuevoEstado->id,
                'usuario_id' => $usuario?->id,
                'notas' => $notas,
            ]);

            // Acciones específicas por estado
            match ($nuevoEstado->codigo) {
                'confirmado' => $this->alConfirmar($pedido),
                'preparando' => $this->alPreparar($pedido),
                'listo' => $this->alEstarListo($pedido),
                'en_camino' => $this->alEnviar($pedido),
                'entregado' => $this->alEntregar($pedido),
                default => null,
            };

            // Disparar evento
            event(new EstadoPedidoCambiado($pedido, $estadoAnterior, $nuevoEstado));
        });
    }

    /**
     * Calcular tiempo estimado de entrega.
     */
    public function calcularTiempoEstimado(Pedido $pedido): array
    {
        $tiempoPreparacion = $pedido->tiempo_estimado_preparacion ?? 20;
        $tiempoEntrega = $pedido->tiempo_estimado_entrega ?? 15;

        $tiempoTranscurrido = $pedido->created_at->diffInMinutes(now());

        $tiempoRestante = max(0, ($tiempoPreparacion + $tiempoEntrega) - $tiempoTranscurrido);

        return [
            'tiempo_preparacion' => $tiempoPreparacion,
            'tiempo_entrega' => $tiempoEntrega,
            'tiempo_total' => $tiempoPreparacion + $tiempoEntrega,
            'tiempo_transcurrido' => $tiempoTranscurrido,
            'tiempo_restante' => $tiempoRestante,
            'hora_estimada' => now()->addMinutes($tiempoRestante)->format('H:i'),
        ];
    }

    /**
     * Generar número de pedido único.
     */
    protected function generarNumeroPedido(): string
    {
        $prefijo = 'SRD';
        $fecha = now()->format('ymd');
        $aleatorio = strtoupper(Str::random(4));

        $numero = "{$prefijo}-{$fecha}-{$aleatorio}";

        // Verificar unicidad
        while (Pedido::where('numero_pedido', $numero)->exists()) {
            $aleatorio = strtoupper(Str::random(4));
            $numero = "{$prefijo}-{$fecha}-{$aleatorio}";
        }

        return $numero;
    }

    /**
     * Calcular tiempo de entrega basado en distancia.
     */
    protected function calcularTiempoEntrega($restaurante, DireccionUsuario $direccion): int
    {
        // Calcular distancia usando Haversine
        $distancia = $restaurante->calcularDistancia(
            $direccion->latitud,
            $direccion->longitud
        );

        // Estimar 3 minutos por kilómetro + 5 minutos base
        return (int) (($distancia * 3) + 5);
    }

    /**
     * Acción al confirmar pedido.
     */
    protected function alConfirmar(Pedido $pedido): void
    {
        // Notificar al usuario
        // Notificar al restaurante
    }

    /**
     * Acción al empezar a preparar.
     */
    protected function alPreparar(Pedido $pedido): void
    {
        $pedido->update(['preparacion_iniciada_at' => now()]);
    }

    /**
     * Acción cuando el pedido está listo.
     */
    protected function alEstarListo(Pedido $pedido): void
    {
        $pedido->update(['listo_at' => now()]);

        // Si es para recoger, notificar al usuario
        if ($pedido->tipo === 'recoger') {
            // Enviar notificación
        }

        // Si es delivery, buscar repartidor disponible
        if ($pedido->tipo === 'delivery') {
            // Asignar repartidor automáticamente o notificar disponibles
        }
    }

    /**
     * Acción cuando el pedido sale para entrega.
     */
    protected function alEnviar(Pedido $pedido): void
    {
        $pedido->update(['enviado_at' => now()]);
        // Notificar al usuario que su pedido va en camino
    }

    /**
     * Acción cuando el pedido se entrega.
     */
    protected function alEntregar(Pedido $pedido): void
    {
        $pedido->update([
            'entregado_at' => now(),
            'pagado' => true,
        ]);

        // Otorgar puntos de lealtad
        $puntos = $this->calcularPuntosLealtad($pedido);
        if ($puntos > 0) {
            $pedido->usuario->agregarPuntosLealtad(
                $puntos,
                'Pedido completado ' . $pedido->numero_pedido,
                $pedido
            );
        }

        // Actualizar estadísticas del restaurante
        $pedido->restaurante->incrementarTotalPedidos();
    }

    /**
     * Calcular puntos de lealtad por pedido.
     */
    protected function calcularPuntosLealtad(Pedido $pedido): int
    {
        // 1 punto por cada RD$100 gastados
        $puntosBase = (int) ($pedido->total / 100);

        // Multiplicador según nivel del usuario
        $multiplicador = $pedido->usuario->nivelLealtad?->multiplicador_puntos ?? 1;

        return (int) ($puntosBase * $multiplicador);
    }
}
