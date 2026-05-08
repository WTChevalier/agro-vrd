<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Producto;
use App\Models\Restaurante;
use Illuminate\Support\Str;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $pedidos = Pedido::where('usuario_id', $request->user()->id)
            ->with(['restaurante', 'detalles'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pedidos
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'restaurante_id' => 'required|exists:restaurantes,id',
            'direccion_entrega' => 'required|string',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        $restaurante = Restaurante::findOrFail($request->restaurante_id);

        // Calcular totales
        $subtotal = 0;
        $productosData = [];

        foreach ($request->productos as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            $precio = $producto->precio_oferta ?? $producto->precio;
            $subtotalItem = $precio * $item['cantidad'];
            $subtotal += $subtotalItem;

            $productosData[] = [
                'producto_id' => $producto->id,
                'nombre_producto' => $producto->nombre,
                'precio_unitario' => $precio,
                'cantidad' => $item['cantidad'],
                'subtotal' => $subtotalItem,
                'notas' => $item['notas'] ?? null,
            ];
        }

        $costoDelivery = $restaurante->costo_delivery;
        $itbis = $subtotal * 0.18;
        $total = $subtotal + $costoDelivery + $itbis;

        // Crear pedido
        $pedido = Pedido::create([
            'codigo' => 'SRD-' . strtoupper(Str::random(8)),
            'usuario_id' => $request->user()->id,
            'restaurante_id' => $restaurante->id,
            'estado' => 'pendiente',
            'subtotal' => $subtotal,
            'costo_delivery' => $costoDelivery,
            'itbis' => $itbis,
            'total' => $total,
            'metodo_pago' => $request->metodo_pago,
            'direccion_entrega' => $request->direccion_entrega,
            'latitud_entrega' => $request->latitud,
            'longitud_entrega' => $request->longitud,
            'notas' => $request->notas,
        ]);

        // Crear detalles
        foreach ($productosData as $data) {
            PedidoDetalle::create(array_merge($data, ['pedido_id' => $pedido->id]));
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'data' => $pedido->load(['detalles', 'restaurante'])
        ], 201);
    }

    public function show($id)
    {
        $pedido = Pedido::where('usuario_id', request()->user()->id)
            ->with(['restaurante', 'detalles', 'repartidor'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pedido
        ]);
    }

    public function tracking($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->with(['restaurante', 'repartidor'])
            ->firstOrFail();

        $tracking = [
            'pedido' => $pedido,
            'estado' => $pedido->estado,
            'repartidor' => $pedido->repartidor ? [
                'nombre' => $pedido->repartidor->nombre,
                'telefono' => $pedido->repartidor->telefono,
                'latitud' => $pedido->repartidor->latitud_actual,
                'longitud' => $pedido->repartidor->longitud_actual,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $tracking
        ]);
    }

    public function cancelar($id)
    {
        $pedido = Pedido::where('usuario_id', request()->user()->id)
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->findOrFail($id);

        $pedido->update([
            'estado' => 'cancelado',
            'cancelado_por' => 'cliente',
            'motivo_cancelacion' => request('motivo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido cancelado'
        ]);
    }
}