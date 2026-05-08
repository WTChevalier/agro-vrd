<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Restaurante;
use App\Models\DireccionUsuario;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $carrito = session('carrito', []);

        if (empty($carrito)) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito está vacío');
        }

        $items = collect($carrito);
        $subtotal = $items->sum(fn($item) => $item['precio'] * $item['cantidad']);

        $restauranteId = $items->first()['restaurante_id'];
        $restaurante = Restaurante::find($restauranteId);
        $costoDelivery = $restaurante ? $restaurante->costo_delivery : 0;

        $cupon = session('cupon');
        $descuento = 0;
        if ($cupon) {
            $descuento = $cupon->tipo === 'porcentaje'
                ? $subtotal * ($cupon->valor / 100)
                : $cupon->valor;
        }

        $itbis = ($subtotal - $descuento) * 0.18;
        $total = $subtotal + $costoDelivery + $itbis - $descuento;

        $direcciones = auth()->check()
            ? DireccionUsuario::where('usuario_id', auth()->id())->get()
            : collect();

        return view('frontend.checkout.index', compact(
            'items', 'subtotal', 'costoDelivery', 'descuento', 'itbis', 'total', 'direcciones'
        ));
    }

    public function procesar(Request $request)
    {
        $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia',
        ]);

        $carrito = session('carrito', []);

        if (empty($carrito)) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito está vacío');
        }

        $items = collect($carrito);
        $subtotal = $items->sum(fn($item) => $item['precio'] * $item['cantidad']);

        $restauranteId = $items->first()['restaurante_id'];
        $restaurante = Restaurante::find($restauranteId);
        $costoDelivery = $restaurante ? $restaurante->costo_delivery : 0;

        $cupon = session('cupon');
        $descuento = 0;
        if ($cupon) {
            $descuento = $cupon->tipo === 'porcentaje'
                ? $subtotal * ($cupon->valor / 100)
                : $cupon->valor;
        }

        $itbis = ($subtotal - $descuento) * 0.18;
        $total = $subtotal + $costoDelivery + $itbis - $descuento;

        // Obtener dirección
        $direccion = '';
        if ($request->direccion_id) {
            $dir = DireccionUsuario::find($request->direccion_id);
            $direccion = $dir ? $dir->direccion_completa : '';
        } elseif ($request->nueva_direccion) {
            $direccion = $request->nueva_direccion['direccion'];

            if (auth()->check()) {
                DireccionUsuario::create([
                    'usuario_id' => auth()->id(),
                    'etiqueta' => $request->nueva_direccion['etiqueta'] ?? 'Casa',
                    'sector' => $request->nueva_direccion['sector'] ?? null,
                    'direccion_completa' => $direccion,
                    'referencia' => $request->nueva_direccion['referencia'] ?? null,
                ]);
            }
        }

        // Crear pedido
        $pedido = Pedido::create([
            'codigo' => 'SRD-' . strtoupper(Str::random(8)),
            'usuario_id' => auth()->id(),
            'restaurante_id' => $restauranteId,
            'estado' => 'pendiente',
            'subtotal' => $subtotal,
            'costo_delivery' => $costoDelivery,
            'descuento' => $descuento,
            'itbis' => $itbis,
            'total' => $total,
            'metodo_pago' => $request->metodo_pago,
            'direccion_entrega' => $direccion,
            'notas' => $request->notas,
            'cupon_id' => $cupon ? $cupon->id : null,
        ]);

        // Crear detalles
        foreach ($items as $item) {
            PedidoDetalle::create([
                'pedido_id' => $pedido->id,
                'producto_id' => $item['producto_id'],
                'nombre_producto' => $item['nombre'],
                'precio_unitario' => $item['precio'],
                'cantidad' => $item['cantidad'],
                'subtotal' => $item['precio'] * $item['cantidad'],
                'notas' => $item['notas'] ?? null,
            ]);
        }

        // Limpiar carrito
        session()->forget('carrito');
        session()->forget('cupon');

        return redirect()->route('checkout.confirmacion', $pedido->codigo);
    }

    public function confirmacion($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->with(['restaurante', 'detalles'])
            ->firstOrFail();

        return view('frontend.checkout.confirmacion', compact('pedido'));
    }
}