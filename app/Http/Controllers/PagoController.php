<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\DireccionUsuario;
use App\Models\EstadoPedido;
use App\Services\PagoService;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador del Proceso de Pago
 *
 * Maneja todo el flujo de checkout: dirección, método de pago y confirmación.
 */
class PagoController extends Controller
{
    public function __construct(
        protected PagoService $pagoService,
        protected PedidoService $pedidoService
    ) {}

    /**
     * Mostrar la página de checkout.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $carrito = $this->obtenerCarrito();

        if (!$carrito || $carrito->items()->count() === 0) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito está vacío');
        }

        $carrito->load([
            'items.plato.imagenPrincipal',
            'items.combo.imagen',
            'restaurante',
            'cupon',
        ]);

        // Verificar que el restaurante esté abierto
        if (!$carrito->restaurante->estaAbiertoAhora()) {
            return redirect()->route('carrito.index')
                ->with('error', 'El restaurante actualmente está cerrado. Por favor, intenta más tarde.');
        }

        // Direcciones del usuario
        $direcciones = auth()->user()->direcciones()
            ->with(['sector.municipio.provincia'])
            ->orderByDesc('es_predeterminada')
            ->get();

        // Dirección seleccionada (de la sesión o predeterminada)
        $direccionSeleccionada = null;
        if (session()->has('pago.direccion_id')) {
            $direccionSeleccionada = $direcciones->find(session('pago.direccion_id'));
        }
        if (!$direccionSeleccionada) {
            $direccionSeleccionada = $direcciones->where('es_predeterminada', true)->first()
                ?? $direcciones->first();
        }

        // Verificar cobertura de delivery
        $coberturaDelivery = null;
        if ($direccionSeleccionada) {
            $coberturaDelivery = $carrito->restaurante->verificarCoberturaDelivery(
                $direccionSeleccionada->latitud,
                $direccionSeleccionada->longitud
            );
        }

        // Métodos de pago disponibles
        $metodosPago = $this->pagoService->obtenerMetodosDisponibles(
            $carrito->restaurante,
            auth()->user()
        );

        // Calcular totales
        $totales = $carrito->calcularTotales();

        // Agregar costo de delivery si aplica
        $tipoPedido = session('pago.tipo', 'delivery');
        if ($tipoPedido === 'delivery' && $coberturaDelivery) {
            $totales['delivery'] = $coberturaDelivery['costo_delivery'];
            $totales['total'] += $coberturaDelivery['costo_delivery'];
        }

        // Propina (opcional)
        $propina = session('pago.propina', 0);
        $totales['propina'] = $propina;
        $totales['total'] += $propina;

        return view('pago.index', compact(
            'carrito',
            'direcciones',
            'direccionSeleccionada',
            'coberturaDelivery',
            'metodosPago',
            'totales',
            'tipoPedido',
            'propina'
        ));
    }

    /**
     * Establecer la dirección de entrega.
     */
    public function establecerDireccion(Request $request): RedirectResponse
    {
        $request->validate([
            'direccion_id' => 'required_if:tipo,delivery|exists:direcciones_usuarios,id',
            'tipo' => 'required|in:delivery,recoger,en_local',
        ]);

        session([
            'pago.direccion_id' => $request->input('direccion_id'),
            'pago.tipo' => $request->input('tipo'),
        ]);

        return redirect()->route('pago.index')
            ->with('exito', 'Dirección actualizada');
    }

    /**
     * Establecer el método de pago.
     */
    public function establecerMetodoPago(Request $request): RedirectResponse
    {
        $request->validate([
            'metodo_pago' => 'required|in:efectivo,tarjeta,billetera,paypal',
        ]);

        session(['pago.metodo_pago' => $request->input('metodo_pago')]);

        return redirect()->route('pago.index')
            ->with('exito', 'Método de pago actualizado');
    }

    /**
     * Establecer la propina.
     */
    public function establecerPropina(Request $request): RedirectResponse
    {
        $request->validate([
            'propina' => 'required|numeric|min:0|max:1000',
        ]);

        session(['pago.propina' => $request->input('propina')]);

        return redirect()->route('pago.index')
            ->with('exito', 'Propina actualizada');
    }

    /**
     * Confirmar y procesar el pedido.
     */
    public function confirmar(Request $request): RedirectResponse
    {
        $request->validate([
            'acepta_terminos' => 'required|accepted',
            'instrucciones' => 'nullable|string|max:500',
        ]);

        $carrito = $this->obtenerCarrito();

        if (!$carrito || $carrito->items()->count() === 0) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito está vacío');
        }

        // Verificar que el restaurante esté abierto
        if (!$carrito->restaurante->estaAbiertoAhora()) {
            return redirect()->route('carrito.index')
                ->with('error', 'El restaurante actualmente está cerrado');
        }

        // Obtener datos del checkout
        $tipo = session('pago.tipo', 'delivery');
        $direccionId = session('pago.direccion_id');
        $metodoPago = session('pago.metodo_pago', 'efectivo');
        $propina = session('pago.propina', 0);

        // Validar dirección para delivery
        $direccion = null;
        if ($tipo === 'delivery') {
            if (!$direccionId) {
                return redirect()->route('pago.index')
                    ->with('error', 'Debes seleccionar una dirección de entrega');
            }

            $direccion = auth()->user()->direcciones()->find($direccionId);
            if (!$direccion) {
                return redirect()->route('pago.index')
                    ->with('error', 'Dirección no válida');
            }

            // Verificar cobertura
            $cobertura = $carrito->restaurante->verificarCoberturaDelivery(
                $direccion->latitud,
                $direccion->longitud
            );

            if (!$cobertura['tiene_cobertura']) {
                return redirect()->route('pago.index')
                    ->with('error', 'Lo sentimos, no tenemos cobertura de delivery en tu zona');
            }
        }

        // Crear el pedido
        try {
            $pedido = $this->pedidoService->crearPedido([
                'usuario_id' => auth()->id(),
                'carrito' => $carrito,
                'tipo' => $tipo,
                'direccion' => $direccion,
                'metodo_pago' => $metodoPago,
                'propina' => $propina,
                'instrucciones' => $request->input('instrucciones'),
            ]);

            // Procesar pago si no es efectivo
            if ($metodoPago !== 'efectivo') {
                $resultadoPago = $this->pagoService->procesarPago($pedido, $metodoPago);

                if (!$resultadoPago['exitoso']) {
                    // Marcar pedido como fallido
                    $pedido->update([
                        'estado_id' => EstadoPedido::where('codigo', 'pago_fallido')->first()->id,
                    ]);

                    return redirect()->route('pago.error', $pedido)
                        ->with('error', $resultadoPago['mensaje']);
                }
            }

            // Limpiar carrito y sesión de pago
            $carrito->items()->delete();
            $carrito->update([
                'restaurante_id' => null,
                'cupon_id' => null,
                'subtotal' => 0,
                'descuento' => 0,
                'total' => 0,
            ]);

            session()->forget(['pago.direccion_id', 'pago.tipo', 'pago.metodo_pago', 'pago.propina']);

            return redirect()->route('pago.exito', $pedido);

        } catch (\Exception $e) {
            report($e);

            return redirect()->route('pago.index')
                ->with('error', 'Ocurrió un error al procesar tu pedido. Por favor, intenta de nuevo.');
        }
    }

    /**
     * Mostrar página de éxito.
     */
    public function exito(Pedido $pedido): View
    {
        // Verificar que el pedido pertenece al usuario
        if ($pedido->usuario_id !== auth()->id()) {
            abort(403);
        }

        $pedido->load([
            'items.plato',
            'items.combo',
            'restaurante',
            'direccionEntrega',
            'estado',
        ]);

        return view('pago.exito', compact('pedido'));
    }

    /**
     * Mostrar página de error.
     */
    public function error(Pedido $pedido = null): View
    {
        if ($pedido && $pedido->usuario_id !== auth()->id()) {
            abort(403);
        }

        return view('pago.error', compact('pedido'));
    }

    /**
     * Obtener el carrito del usuario autenticado.
     */
    protected function obtenerCarrito(): ?Carrito
    {
        return Carrito::where('usuario_id', auth()->id())->first();
    }
}
