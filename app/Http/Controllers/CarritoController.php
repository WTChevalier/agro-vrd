<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\ItemCarrito;
use App\Models\Plato;
use App\Models\Combo;
use App\Models\Cupon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador del Carrito de Compras
 *
 * Maneja todas las operaciones del carrito de compras.
 */
class CarritoController extends Controller
{
    /**
     * Mostrar el carrito de compras.
     */
    public function index(Request $request): View
    {
        $carrito = $this->obtenerOCrearCarrito($request);

        $carrito->load([
            'items.plato.imagenPrincipal',
            'items.combo.imagen',
            'items.opciones',
            'restaurante',
            'cupon',
        ]);

        // Calcular totales
        $totales = $carrito->calcularTotales();

        return view('carrito.index', compact('carrito', 'totales'));
    }

    /**
     * Agregar un item al carrito.
     */
    public function agregar(Request $request): RedirectResponse
    {
        $request->validate([
            'tipo' => 'required|in:plato,combo',
            'id' => 'required|integer',
            'cantidad' => 'required|integer|min:1|max:99',
            'opciones' => 'nullable|array',
            'instrucciones_especiales' => 'nullable|string|max:500',
        ]);

        $carrito = $this->obtenerOCrearCarrito($request);

        // Obtener el producto
        if ($request->input('tipo') === 'plato') {
            $producto = Plato::findOrFail($request->input('id'));
            $restauranteId = $producto->restaurante_id;
            $precioUnitario = $producto->precio_con_descuento;
        } else {
            $producto = Combo::findOrFail($request->input('id'));
            $restauranteId = $producto->restaurante_id;
            $precioUnitario = $producto->precio_final;
        }

        // Verificar si el carrito tiene items de otro restaurante
        if ($carrito->restaurante_id && $carrito->restaurante_id !== $restauranteId) {
            return back()->with('error', 'Tu carrito contiene productos de otro restaurante. ¿Deseas vaciarlo para agregar este producto?')
                ->with('confirmar_vaciar', true)
                ->with('producto_pendiente', [
                    'tipo' => $request->input('tipo'),
                    'id' => $request->input('id'),
                    'cantidad' => $request->input('cantidad'),
                    'opciones' => $request->input('opciones'),
                    'instrucciones_especiales' => $request->input('instrucciones_especiales'),
                ]);
        }

        // Actualizar restaurante del carrito si es necesario
        if (!$carrito->restaurante_id) {
            $carrito->update(['restaurante_id' => $restauranteId]);
        }

        // Calcular precio con opciones
        $precioOpciones = 0;
        $opcionesSeleccionadas = [];
        if ($request->has('opciones') && is_array($request->input('opciones'))) {
            foreach ($request->input('opciones') as $opcionId => $valores) {
                // Aquí se procesarían las opciones seleccionadas
                // y se calcularía el precio adicional
            }
        }

        // Buscar si ya existe este item en el carrito
        $itemExistente = $carrito->items()
            ->where('tipo_producto', $request->input('tipo'))
            ->where('producto_id', $request->input('id'))
            ->where('instrucciones_especiales', $request->input('instrucciones_especiales'))
            ->first();

        if ($itemExistente) {
            // Actualizar cantidad
            $itemExistente->update([
                'cantidad' => $itemExistente->cantidad + $request->input('cantidad'),
            ]);
        } else {
            // Crear nuevo item
            $carrito->items()->create([
                'tipo_producto' => $request->input('tipo'),
                'producto_id' => $request->input('id'),
                'cantidad' => $request->input('cantidad'),
                'precio_unitario' => $precioUnitario,
                'precio_opciones' => $precioOpciones,
                'instrucciones_especiales' => $request->input('instrucciones_especiales'),
            ]);
        }

        // Recalcular totales del carrito
        $carrito->recalcularTotales();

        return back()->with('exito', '¡Producto agregado al carrito!');
    }

    /**
     * Actualizar la cantidad de un item.
     */
    public function actualizar(Request $request, ItemCarrito $item): RedirectResponse
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1|max:99',
        ]);

        // Verificar que el item pertenece al carrito del usuario
        $carrito = $this->obtenerOCrearCarrito($request);
        if ($item->carrito_id !== $carrito->id) {
            abort(403, 'No tienes permiso para modificar este item');
        }

        $item->update([
            'cantidad' => $request->input('cantidad'),
        ]);

        $carrito->recalcularTotales();

        return back()->with('exito', 'Cantidad actualizada');
    }

    /**
     * Eliminar un item del carrito.
     */
    public function eliminar(Request $request, ItemCarrito $item): RedirectResponse
    {
        $carrito = $this->obtenerOCrearCarrito($request);

        if ($item->carrito_id !== $carrito->id) {
            abort(403, 'No tienes permiso para eliminar este item');
        }

        $item->delete();

        // Si el carrito quedó vacío, limpiar restaurante y cupón
        if ($carrito->items()->count() === 0) {
            $carrito->update([
                'restaurante_id' => null,
                'cupon_id' => null,
            ]);
        }

        $carrito->recalcularTotales();

        return back()->with('exito', 'Producto eliminado del carrito');
    }

    /**
     * Vaciar el carrito completamente.
     */
    public function vaciar(Request $request): RedirectResponse
    {
        $carrito = $this->obtenerOCrearCarrito($request);

        $carrito->items()->delete();
        $carrito->update([
            'restaurante_id' => null,
            'cupon_id' => null,
            'subtotal' => 0,
            'descuento' => 0,
            'total' => 0,
        ]);

        return back()->with('exito', 'Carrito vaciado correctamente');
    }

    /**
     * Aplicar un cupón de descuento.
     */
    public function aplicarCupon(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => 'required|string|max:50',
        ]);

        $carrito = $this->obtenerOCrearCarrito($request);

        if ($carrito->items()->count() === 0) {
            return back()->with('error', 'Tu carrito está vacío');
        }

        // Buscar el cupón
        $cupon = Cupon::where('codigo', strtoupper($request->input('codigo')))
            ->where('activo', true)
            ->first();

        if (!$cupon) {
            return back()->with('error', 'Cupón no válido o expirado');
        }

        // Validar el cupón
        $validacion = $cupon->validarParaCarrito($carrito, auth()->user());
        if (!$validacion['valido']) {
            return back()->with('error', $validacion['mensaje']);
        }

        // Aplicar cupón
        $carrito->update(['cupon_id' => $cupon->id]);
        $carrito->recalcularTotales();

        return back()->with('exito', "¡Cupón '{$cupon->codigo}' aplicado correctamente!");
    }

    /**
     * Remover el cupón aplicado.
     */
    public function removerCupon(Request $request): RedirectResponse
    {
        $carrito = $this->obtenerOCrearCarrito($request);

        $carrito->update(['cupon_id' => null]);
        $carrito->recalcularTotales();

        return back()->with('exito', 'Cupón removido');
    }

    /**
     * Obtener o crear el carrito del usuario/sesión.
     */
    protected function obtenerOCrearCarrito(Request $request): Carrito
    {
        if (auth()->check()) {
            // Usuario autenticado: buscar por usuario_id
            $carrito = Carrito::firstOrCreate(
                ['usuario_id' => auth()->id()],
                ['session_id' => session()->getId()]
            );

            // Migrar carrito de sesión si existe
            $carritoSesion = Carrito::where('session_id', session()->getId())
                ->whereNull('usuario_id')
                ->first();

            if ($carritoSesion && $carritoSesion->items()->count() > 0) {
                // Migrar items al carrito del usuario
                foreach ($carritoSesion->items as $item) {
                    $carrito->items()->create($item->only([
                        'tipo_producto',
                        'producto_id',
                        'cantidad',
                        'precio_unitario',
                        'precio_opciones',
                        'instrucciones_especiales',
                    ]));
                }

                if (!$carrito->restaurante_id && $carritoSesion->restaurante_id) {
                    $carrito->update(['restaurante_id' => $carritoSesion->restaurante_id]);
                }

                $carritoSesion->items()->delete();
                $carritoSesion->delete();
                $carrito->recalcularTotales();
            }
        } else {
            // Usuario no autenticado: usar session_id
            $carrito = Carrito::firstOrCreate(
                ['session_id' => session()->getId()],
                ['usuario_id' => null]
            );
        }

        return $carrito;
    }
}
