<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Carrito;
use App\Models\EstadoPedido;
use App\Services\PedidoService;
use App\Services\PagoService;
use App\Http\Resources\PedidoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de Pedidos
 *
 * Endpoints autenticados para gestión de pedidos del usuario.
 */
class PedidoApiController extends Controller
{
    public function __construct(
        protected PedidoService $pedidoService,
        protected PagoService $pagoService
    ) {}

    /**
     * Listar pedidos del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth()->user()->pedidos()
            ->with(['restaurante', 'estado', 'items'])
            ->orderByDesc('created_at');

        // Filtrar por estado
        if ($request->filled('estado')) {
            $query->whereHas('estado', function ($q) use ($request) {
                $q->where('codigo', $request->input('estado'));
            });
        }

        // Filtrar por fecha
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        $pedidos = $query->paginate($request->input('por_pagina', 10));

        return response()->json([
            'exito' => true,
            'datos' => PedidoResource::collection($pedidos),
            'paginacion' => [
                'total' => $pedidos->total(),
                'por_pagina' => $pedidos->perPage(),
                'pagina_actual' => $pedidos->currentPage(),
                'ultima_pagina' => $pedidos->lastPage(),
            ],
        ]);
    }

    /**
     * Crear un nuevo pedido.
     */
    public function crear(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|in:delivery,recoger,en_local',
            'direccion_id' => 'required_if:tipo,delivery|exists:direcciones_usuarios,id',
            'metodo_pago' => 'required|in:efectivo,tarjeta,billetera,paypal',
            'propina' => 'nullable|numeric|min:0',
            'instrucciones' => 'nullable|string|max:500',
            'usar_puntos' => 'nullable|boolean',
        ]);

        // Obtener carrito
        $carrito = Carrito::where('usuario_id', auth()->id())->first();

        if (!$carrito || $carrito->items()->count() === 0) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Tu carrito está vacío',
            ], 400);
        }

        // Verificar que el restaurante está abierto
        if (!$carrito->restaurante->estaAbiertoAhora()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'El restaurante actualmente está cerrado',
            ], 400);
        }

        // Validar dirección para delivery
        $direccion = null;
        if ($request->input('tipo') === 'delivery') {
            $direccion = auth()->user()->direcciones()->find($request->input('direccion_id'));

            if (!$direccion) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'Dirección no válida',
                ], 400);
            }

            // Verificar cobertura
            $cobertura = $carrito->restaurante->verificarCoberturaDelivery(
                $direccion->latitud,
                $direccion->longitud
            );

            if (!$cobertura['tiene_cobertura']) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'No tenemos cobertura de delivery en tu zona',
                ], 400);
            }
        }

        try {
            // Crear el pedido
            $pedido = $this->pedidoService->crearPedido([
                'usuario_id' => auth()->id(),
                'carrito' => $carrito,
                'tipo' => $request->input('tipo'),
                'direccion' => $direccion,
                'metodo_pago' => $request->input('metodo_pago'),
                'propina' => $request->input('propina', 0),
                'instrucciones' => $request->input('instrucciones'),
                'usar_puntos' => $request->boolean('usar_puntos'),
            ]);

            // Procesar pago si no es efectivo
            if ($request->input('metodo_pago') !== 'efectivo') {
                $resultadoPago = $this->pagoService->procesarPago(
                    $pedido,
                    $request->input('metodo_pago')
                );

                if (!$resultadoPago['exitoso']) {
                    $pedido->update([
                        'estado_id' => EstadoPedido::where('codigo', 'pago_fallido')->first()->id,
                    ]);

                    return response()->json([
                        'exito' => false,
                        'mensaje' => $resultadoPago['mensaje'],
                        'pedido_id' => $pedido->id,
                    ], 400);
                }
            }

            // Limpiar carrito
            $carrito->items()->delete();
            $carrito->update([
                'restaurante_id' => null,
                'cupon_id' => null,
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0,
            ]);

            $pedido->load(['items', 'restaurante', 'estado']);

            return response()->json([
                'exito' => true,
                'mensaje' => '¡Pedido creado exitosamente!',
                'datos' => new PedidoResource($pedido),
            ], 201);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'exito' => false,
                'mensaje' => 'Error al procesar el pedido',
            ], 500);
        }
    }

    /**
     * Mostrar detalle de un pedido.
     */
    public function mostrar(Pedido $pedido): JsonResponse
    {
        if ($pedido->usuario_id !== auth()->id()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No autorizado',
            ], 403);
        }

        $pedido->load([
            'items.plato',
            'items.combo',
            'restaurante',
            'estado',
            'historialEstados.estado',
            'direccionEntrega',
            'repartidor.usuario',
        ]);

        return response()->json([
            'exito' => true,
            'datos' => new PedidoResource($pedido),
        ]);
    }

    /**
     * Cancelar un pedido.
     */
    public function cancelar(Request $request, Pedido $pedido): JsonResponse
    {
        if ($pedido->usuario_id !== auth()->id()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No autorizado',
            ], 403);
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        // Verificar que se puede cancelar
        $estadosCancelables = ['pendiente', 'confirmado'];
        if (!in_array($pedido->estado->codigo, $estadosCancelables)) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Este pedido ya no se puede cancelar',
            ], 400);
        }

        try {
            $this->pedidoService->cancelarPedido(
                $pedido,
                $request->input('motivo'),
                auth()->user()
            );

            $pedido->refresh()->load(['estado']);

            return response()->json([
                'exito' => true,
                'mensaje' => 'Pedido cancelado correctamente',
                'datos' => new PedidoResource($pedido),
            ]);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'exito' => false,
                'mensaje' => 'No se pudo cancelar el pedido',
            ], 500);
        }
    }

    /**
     * Obtener información de seguimiento del pedido.
     */
    public function seguimiento(Pedido $pedido): JsonResponse
    {
        if ($pedido->usuario_id !== auth()->id()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No autorizado',
            ], 403);
        }

        $pedido->load([
            'estado',
            'historialEstados.estado',
            'repartidor.usuario',
            'restaurante',
        ]);

        // Ubicación del repartidor si está en camino
        $ubicacionRepartidor = null;
        if ($pedido->repartidor && $pedido->estado->codigo === 'en_camino') {
            $ubicacionRepartidor = [
                'latitud' => $pedido->repartidor->latitud_actual,
                'longitud' => $pedido->repartidor->longitud_actual,
                'ultima_actualizacion' => $pedido->repartidor->ultima_ubicacion_at,
            ];
        }

        // Tiempo estimado
        $tiempoEstimado = $this->pedidoService->calcularTiempoEstimado($pedido);

        // Flujo de estados
        $flujoEstados = EstadoPedido::orderBy('orden')->get()->map(function ($estado) use ($pedido) {
            $historial = $pedido->historialEstados->firstWhere('estado_id', $estado->id);

            return [
                'codigo' => $estado->codigo,
                'nombre' => $estado->nombre,
                'completado' => $historial !== null,
                'fecha' => $historial?->created_at,
                'es_actual' => $pedido->estado_id === $estado->id,
            ];
        });

        return response()->json([
            'exito' => true,
            'estado_actual' => [
                'codigo' => $pedido->estado->codigo,
                'nombre' => $pedido->estado->nombre,
            ],
            'flujo_estados' => $flujoEstados,
            'ubicacion_repartidor' => $ubicacionRepartidor,
            'tiempo_estimado' => $tiempoEstimado,
            'repartidor' => $pedido->repartidor ? [
                'nombre' => $pedido->repartidor->usuario->nombre_completo,
                'telefono' => $pedido->repartidor->telefono,
                'foto' => $pedido->repartidor->usuario->avatar,
                'calificacion' => $pedido->repartidor->calificacion,
            ] : null,
        ]);
    }

    /**
     * Calificar un pedido.
     */
    public function calificar(Request $request, Pedido $pedido): JsonResponse
    {
        if ($pedido->usuario_id !== auth()->id()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No autorizado',
            ], 403);
        }

        if ($pedido->estado->codigo !== 'entregado') {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Solo puedes calificar pedidos entregados',
            ], 400);
        }

        if ($pedido->resenaRestaurante) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Ya calificaste este pedido',
            ], 400);
        }

        $request->validate([
            'calificacion_restaurante' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:1000',
            'calificacion_comida' => 'required|integer|min:1|max:5',
            'calificacion_servicio' => 'required|integer|min:1|max:5',
            'calificacion_entrega' => 'nullable|integer|min:1|max:5',
        ]);

        // Crear reseña
        $pedido->resenaRestaurante()->create([
            'usuario_id' => auth()->id(),
            'restaurante_id' => $pedido->restaurante_id,
            'calificacion' => $request->input('calificacion_restaurante'),
            'calificacion_comida' => $request->input('calificacion_comida'),
            'calificacion_servicio' => $request->input('calificacion_servicio'),
            'calificacion_entrega' => $request->input('calificacion_entrega'),
            'comentario' => $request->input('comentario'),
        ]);

        // Actualizar calificación del restaurante
        $pedido->restaurante->recalcularCalificacion();

        return response()->json([
            'exito' => true,
            'mensaje' => '¡Gracias por tu calificación!',
        ]);
    }

    /**
     * Reordenar un pedido anterior.
     */
    public function reordenar(Pedido $pedido): JsonResponse
    {
        if ($pedido->usuario_id !== auth()->id()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No autorizado',
            ], 403);
        }

        $pedido->load(['items.plato', 'items.combo']);

        // Obtener o crear carrito
        $carrito = Carrito::firstOrCreate(
            ['usuario_id' => auth()->id()],
            ['session_id' => session()->getId()]
        );

        // Verificar si hay items de otro restaurante
        if ($carrito->restaurante_id && $carrito->restaurante_id !== $pedido->restaurante_id) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Tu carrito contiene productos de otro restaurante',
                'requiere_confirmacion' => true,
            ], 409);
        }

        // Vaciar carrito actual
        $carrito->items()->delete();
        $carrito->update(['restaurante_id' => $pedido->restaurante_id]);

        // Agregar items
        $itemsNoDisponibles = [];
        foreach ($pedido->items as $item) {
            $producto = $item->tipo_producto === 'plato' ? $item->plato : $item->combo;

            if (!$producto || !$producto->disponible) {
                $itemsNoDisponibles[] = $item->nombre_producto;
                continue;
            }

            $carrito->items()->create([
                'tipo_producto' => $item->tipo_producto,
                'producto_id' => $item->producto_id,
                'cantidad' => $item->cantidad,
                'precio_unitario' => $producto->precio_con_descuento ?? $producto->precio_final,
                'precio_opciones' => 0,
                'instrucciones_especiales' => $item->instrucciones_especiales,
            ]);
        }

        $carrito->recalcularTotales();

        return response()->json([
            'exito' => true,
            'mensaje' => '¡Productos agregados al carrito!',
            'items_no_disponibles' => $itemsNoDisponibles,
            'carrito' => [
                'total_items' => $carrito->items()->count(),
                'total' => $carrito->total,
            ],
        ]);
    }

    /**
     * Obtener recibo del pedido.
     */
    public function recibo(Pedido $pedido): JsonResponse
    {
        if ($pedido->usuario_id !== auth()->id()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No autorizado',
            ], 403);
        }

        $pedido->load([
            'items.plato',
            'items.combo',
            'restaurante',
            'direccionEntrega',
            'cupon',
        ]);

        return response()->json([
            'exito' => true,
            'recibo' => [
                'numero_pedido' => $pedido->numero_pedido,
                'fecha' => $pedido->created_at->format('d/m/Y H:i'),
                'restaurante' => $pedido->restaurante->nombre,
                'tipo' => $pedido->tipo,
                'items' => $pedido->items->map(fn($item) => [
                    'nombre' => $item->nombre_producto,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'subtotal' => $item->subtotal,
                ]),
                'subtotal' => $pedido->subtotal,
                'descuento' => $pedido->descuento,
                'costo_delivery' => $pedido->costo_delivery,
                'propina' => $pedido->propina,
                'total' => $pedido->total,
                'metodo_pago' => $pedido->metodo_pago,
                'cupon' => $pedido->cupon?->codigo,
            ],
        ]);
    }
}
