<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Carrito;
use App\Models\EstadoPedido;
use App\Models\ResenaRestaurante;
use App\Models\ResenaPlato;
use App\Services\PedidoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador de Pedidos del Usuario
 *
 * Maneja la visualización, seguimiento y gestión de pedidos del usuario.
 */
class PedidoController extends Controller
{
    public function __construct(
        protected PedidoService $pedidoService
    ) {}

    /**
     * Listar los pedidos del usuario.
     */
    public function index(Request $request): View
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

        $pedidos = $query->paginate(10)->withQueryString();

        // Estadísticas del usuario
        $estadisticas = [
            'total_pedidos' => auth()->user()->pedidos()->count(),
            'pedidos_completados' => auth()->user()->pedidos()
                ->whereHas('estado', fn($q) => $q->where('codigo', 'entregado'))
                ->count(),
            'total_gastado' => auth()->user()->pedidos()
                ->whereHas('estado', fn($q) => $q->where('codigo', 'entregado'))
                ->sum('total'),
        ];

        return view('pedidos.index', compact('pedidos', 'estadisticas'));
    }

    /**
     * Mostrar el detalle de un pedido.
     */
    public function mostrar(Pedido $pedido): View
    {
        // Verificar que el pedido pertenece al usuario
        $this->autorizarPedido($pedido);

        $pedido->load([
            'items.plato.imagenPrincipal',
            'items.combo.imagen',
            'items.opciones',
            'restaurante.imagenPrincipal',
            'estado',
            'historialEstados.estado',
            'historialEstados.usuario',
            'direccionEntrega.sector.municipio.provincia',
            'repartidor.usuario',
            'resenaRestaurante',
            'cupon',
        ]);

        // Verificar si puede calificar
        $puedeCalificar = $pedido->estado->codigo === 'entregado'
            && !$pedido->resenaRestaurante
            && $pedido->created_at->diffInDays(now()) <= 7;

        return view('pedidos.mostrar', compact('pedido', 'puedeCalificar'));
    }

    /**
     * Mostrar el seguimiento en tiempo real del pedido.
     */
    public function seguimiento(Pedido $pedido): View
    {
        $this->autorizarPedido($pedido);

        $pedido->load([
            'restaurante',
            'estado',
            'historialEstados.estado',
            'repartidor.usuario',
            'direccionEntrega',
        ]);

        // Estados del flujo de pedido
        $flujoEstados = EstadoPedido::orderBy('orden')->get();

        // Ubicación del repartidor (si está en camino)
        $ubicacionRepartidor = null;
        if ($pedido->repartidor && in_array($pedido->estado->codigo, ['en_camino'])) {
            $ubicacionRepartidor = [
                'latitud' => $pedido->repartidor->latitud_actual,
                'longitud' => $pedido->repartidor->longitud_actual,
                'ultima_actualizacion' => $pedido->repartidor->ultima_ubicacion_at,
            ];
        }

        // Tiempo estimado
        $tiempoEstimado = $this->pedidoService->calcularTiempoEstimado($pedido);

        return view('pedidos.seguimiento', compact(
            'pedido',
            'flujoEstados',
            'ubicacionRepartidor',
            'tiempoEstimado'
        ));
    }

    /**
     * Cancelar un pedido.
     */
    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizarPedido($pedido);

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        // Verificar que se puede cancelar
        $estadosCancelables = ['pendiente', 'confirmado'];
        if (!in_array($pedido->estado->codigo, $estadosCancelables)) {
            return back()->with('error', 'Este pedido ya no se puede cancelar');
        }

        try {
            $this->pedidoService->cancelarPedido($pedido, $request->input('motivo'), auth()->user());

            return redirect()->route('pedidos.mostrar', $pedido)
                ->with('exito', 'Pedido cancelado correctamente');

        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'No se pudo cancelar el pedido. Por favor, contacta soporte.');
        }
    }

    /**
     * Reordenar un pedido anterior.
     */
    public function reordenar(Pedido $pedido): RedirectResponse
    {
        $this->autorizarPedido($pedido);

        $pedido->load(['items.plato', 'items.combo']);

        // Obtener o crear carrito
        $carrito = Carrito::firstOrCreate(
            ['usuario_id' => auth()->id()],
            ['session_id' => session()->getId()]
        );

        // Verificar si hay items de otro restaurante
        if ($carrito->restaurante_id && $carrito->restaurante_id !== $pedido->restaurante_id) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito contiene productos de otro restaurante')
                ->with('confirmar_vaciar_reordenar', $pedido->id);
        }

        // Vaciar carrito actual
        $carrito->items()->delete();
        $carrito->update(['restaurante_id' => $pedido->restaurante_id]);

        // Agregar items del pedido anterior
        $itemsNoDisponibles = [];
        foreach ($pedido->items as $item) {
            // Verificar disponibilidad
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

        $mensaje = '¡Productos agregados al carrito!';
        if (count($itemsNoDisponibles) > 0) {
            $mensaje .= ' Algunos productos ya no están disponibles: ' . implode(', ', $itemsNoDisponibles);
        }

        return redirect()->route('carrito.index')->with('exito', $mensaje);
    }

    /**
     * Calificar un pedido (restaurante y platos).
     */
    public function calificar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizarPedido($pedido);

        // Verificar que se puede calificar
        if ($pedido->estado->codigo !== 'entregado') {
            return back()->with('error', 'Solo puedes calificar pedidos entregados');
        }

        if ($pedido->resenaRestaurante) {
            return back()->with('error', 'Ya calificaste este pedido');
        }

        if ($pedido->created_at->diffInDays(now()) > 7) {
            return back()->with('error', 'El período para calificar este pedido ha expirado');
        }

        $request->validate([
            'calificacion_restaurante' => 'required|integer|min:1|max:5',
            'comentario_restaurante' => 'nullable|string|max:1000',
            'calificacion_comida' => 'required|integer|min:1|max:5',
            'calificacion_servicio' => 'required|integer|min:1|max:5',
            'calificacion_entrega' => 'required_if:tipo,delivery|nullable|integer|min:1|max:5',
            'calificaciones_platos' => 'nullable|array',
            'calificaciones_platos.*.calificacion' => 'required|integer|min:1|max:5',
            'calificaciones_platos.*.comentario' => 'nullable|string|max:500',
        ]);

        // Crear reseña del restaurante
        $resenaRestaurante = ResenaRestaurante::create([
            'usuario_id' => auth()->id(),
            'restaurante_id' => $pedido->restaurante_id,
            'pedido_id' => $pedido->id,
            'calificacion' => $request->input('calificacion_restaurante'),
            'calificacion_comida' => $request->input('calificacion_comida'),
            'calificacion_servicio' => $request->input('calificacion_servicio'),
            'calificacion_entrega' => $request->input('calificacion_entrega'),
            'comentario' => $request->input('comentario_restaurante'),
        ]);

        // Crear reseñas de platos individuales
        if ($request->has('calificaciones_platos')) {
            foreach ($request->input('calificaciones_platos') as $platoId => $datos) {
                ResenaPlato::create([
                    'usuario_id' => auth()->id(),
                    'plato_id' => $platoId,
                    'pedido_id' => $pedido->id,
                    'calificacion' => $datos['calificacion'],
                    'comentario' => $datos['comentario'] ?? null,
                ]);
            }
        }

        // Actualizar calificación del restaurante
        $pedido->restaurante->recalcularCalificacion();

        // Otorgar puntos de lealtad por calificar
        if (auth()->user()->nivelLealtad) {
            auth()->user()->agregarPuntosLealtad(
                10,
                'Calificación de pedido',
                $pedido
            );
        }

        return redirect()->route('pedidos.mostrar', $pedido)
            ->with('exito', '¡Gracias por tu calificación!');
    }

    /**
     * Descargar el recibo del pedido.
     */
    public function recibo(Pedido $pedido)
    {
        $this->autorizarPedido($pedido);

        $pedido->load([
            'items.plato',
            'items.combo',
            'restaurante',
            'direccionEntrega',
            'usuario',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pedidos.recibo-pdf', compact('pedido'));

        return $pdf->download("recibo-pedido-{$pedido->numero_pedido}.pdf");
    }

    /**
     * Verificar que el pedido pertenece al usuario autenticado.
     */
    protected function autorizarPedido(Pedido $pedido): void
    {
        if ($pedido->usuario_id !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este pedido');
        }
    }
}
