<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Restaurante;
use App\Models\Cupon;

class CarritoController extends Controller
{
    public function index(Request $request)
    {
        $carrito = session('carrito', []);
        $items = collect($carrito);

        $subtotal = $items->sum(function ($item) {
            return $item['precio'] * $item['cantidad'];
        });

        $costoDelivery = 0;
        $descuento = 0;

        if ($items->count() > 0) {
            $restauranteId = $items->first()['restaurante_id'];
            $restaurante = Restaurante::find($restauranteId);
            $costoDelivery = $restaurante ? $restaurante->costo_delivery : 0;
        }

        // Aplicar cupón si existe
        if ($request->cupon) {
            $cupon = Cupon::where('codigo', $request->cupon)
                ->where('activo', true)
                ->where('fecha_inicio', '<=', now())
                ->where('fecha_fin', '>=', now())
                ->first();

            if ($cupon) {
                if ($cupon->tipo === 'porcentaje') {
                    $descuento = $subtotal * ($cupon->valor / 100);
                } else {
                    $descuento = $cupon->valor;
                }
                session(['cupon' => $cupon]);
            }
        }

        $total = $subtotal + $costoDelivery - $descuento;

        return view('frontend.carrito', compact('items', 'subtotal', 'costoDelivery', 'descuento', 'total'));
    }

    public function agregar(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $producto = Producto::with('restaurante')->findOrFail($request->producto_id);
        $carrito = session('carrito', []);

        // Verificar si hay productos de otro restaurante
        if (!empty($carrito)) {
            $primerItem = reset($carrito);
            if ($primerItem['restaurante_id'] !== $producto->restaurante_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes ordenar de un restaurante a la vez. ¿Deseas vaciar el carrito?'
                ]);
            }
        }

        $key = $producto->id . '_' . md5($request->notas ?? '');

        if (isset($carrito[$key])) {
            $carrito[$key]['cantidad'] += $request->cantidad;
        } else {
            $carrito[$key] = [
                'producto_id' => $producto->id,
                'restaurante_id' => $producto->restaurante_id,
                'restaurante_nombre' => $producto->restaurante->nombre,
                'nombre' => $producto->nombre,
                'precio' => $producto->precio_oferta ?? $producto->precio,
                'imagen' => $producto->imagen,
                'cantidad' => $request->cantidad,
                'notas' => $request->notas,
            ];
        }

        session(['carrito' => $carrito]);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito',
            'total_items' => count($carrito)
        ]);
    }

    public function actualizar(Request $request, $key)
    {
        $carrito = session('carrito', []);

        if (isset($carrito[$key])) {
            $cantidad = $request->cantidad;
            if ($cantidad > 0) {
                $carrito[$key]['cantidad'] = $cantidad;
            } else {
                unset($carrito[$key]);
            }
            session(['carrito' => $carrito]);
        }

        return redirect()->route('carrito.index')->with('success', 'Carrito actualizado');
    }

    public function eliminar($key)
    {
        $carrito = session('carrito', []);

        if (isset($carrito[$key])) {
            unset($carrito[$key]);
            session(['carrito' => $carrito]);
        }

        return redirect()->route('carrito.index')->with('success', 'Producto eliminado');
    }

    public function vaciar()
    {
        session()->forget('carrito');
        session()->forget('cupon');

        return redirect()->route('carrito.index')->with('success', 'Carrito vaciado');
    }
}