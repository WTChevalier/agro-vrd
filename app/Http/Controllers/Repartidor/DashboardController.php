<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Repartidor;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $repartidor = auth()->user()->repartidor;

        $hoy = Carbon::today();

        $entregasHoy = Pedido::where('repartidor_id', $repartidor->id)
            ->whereDate('created_at', $hoy)
            ->where('estado', 'entregado')
            ->count();

        $gananciasHoy = Pedido::where('repartidor_id', $repartidor->id)
            ->whereDate('created_at', $hoy)
            ->where('estado', 'entregado')
            ->sum('comision_repartidor');

        $pedidoActivo = Pedido::where('repartidor_id', $repartidor->id)
            ->whereIn('estado', ['en_camino'])
            ->with(['restaurante', 'usuario'])
            ->first();

        $pedidosDisponibles = Pedido::where('estado', 'listo')
            ->whereNull('repartidor_id')
            ->with(['restaurante'])
            ->orderBy('created_at')
            ->take(10)
            ->get();

        return view('repartidor.dashboard', compact(
            'repartidor',
            'entregasHoy',
            'gananciasHoy',
            'pedidoActivo',
            'pedidosDisponibles'
        ));
    }

    public function aceptarPedido($id)
    {
        $repartidor = auth()->user()->repartidor;

        // Verificar si ya tiene un pedido activo
        $pedidoActivo = Pedido::where('repartidor_id', $repartidor->id)
            ->whereIn('estado', ['en_camino'])
            ->exists();

        if ($pedidoActivo) {
            return back()->with('error', 'Ya tienes un pedido en curso');
        }

        $pedido = Pedido::where('estado', 'listo')
            ->whereNull('repartidor_id')
            ->findOrFail($id);

        $pedido->update([
            'repartidor_id' => $repartidor->id,
            'estado' => 'en_camino',
            'hora_recogida' => now(),
        ]);

        return redirect()->route('repartidor.pedido.activo')
            ->with('success', 'Pedido aceptado');
    }

    public function pedidoActivo()
    {
        $repartidor = auth()->user()->repartidor;

        $pedido = Pedido::where('repartidor_id', $repartidor->id)
            ->whereIn('estado', ['en_camino'])
            ->with(['restaurante', 'usuario', 'detalles'])
            ->first();

        if (!$pedido) {
            return redirect()->route('repartidor.dashboard')
                ->with('info', 'No tienes pedidos activos');
        }

        return view('repartidor.pedido-activo', compact('pedido'));
    }

    public function confirmarEntrega($id)
    {
        $repartidor = auth()->user()->repartidor;

        $pedido = Pedido::where('repartidor_id', $repartidor->id)
            ->where('id', $id)
            ->where('estado', 'en_camino')
            ->firstOrFail();

        $pedido->update([
            'estado' => 'entregado',
            'hora_entrega' => now(),
        ]);

        // Actualizar estadísticas del repartidor
        $repartidor->increment('total_entregas');

        return redirect()->route('repartidor.dashboard')
            ->with('success', '¡Entrega confirmada!');
    }

    public function actualizarUbicacion(Request $request)
    {
        $repartidor = auth()->user()->repartidor;

        $repartidor->update([
            'latitud_actual' => $request->latitud,
            'longitud_actual' => $request->longitud,
            'ultima_ubicacion' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function historial(Request $request)
    {
        $repartidor = auth()->user()->repartidor;

        $pedidos = Pedido::where('repartidor_id', $repartidor->id)
            ->where('estado', 'entregado')
            ->with(['restaurante'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalGanancias = Pedido::where('repartidor_id', $repartidor->id)
            ->where('estado', 'entregado')
            ->sum('comision_repartidor');

        return view('repartidor.historial', compact('pedidos', 'totalGanancias'));
    }

    public function toggleDisponibilidad()
    {
        $repartidor = auth()->user()->repartidor;

        $repartidor->update([
            'disponible' => !$repartidor->disponible
        ]);

        return response()->json([
            'success' => true,
            'disponible' => $repartidor->disponible
        ]);
    }
}