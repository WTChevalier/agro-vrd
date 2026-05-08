<?php

namespace App\Http\Controllers\Restaurante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $restaurante = auth()->user()->restaurante;

        $pedidos = Pedido::where('restaurante_id', $restaurante->id)
            ->when($request->estado, function ($q) use ($request) {
                $q->where('estado', $request->estado);
            })
            ->when($request->fecha, function ($q) use ($request) {
                $q->whereDate('created_at', $request->fecha);
            })
            ->with(['detalles', 'usuario', 'repartidor'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $contadores = [
            'pendientes' => Pedido::where('restaurante_id', $restaurante->id)->where('estado', 'pendiente')->count(),
            'confirmados' => Pedido::where('restaurante_id', $restaurante->id)->where('estado', 'confirmado')->count(),
            'preparando' => Pedido::where('restaurante_id', $restaurante->id)->where('estado', 'preparando')->count(),
            'en_camino' => Pedido::where('restaurante_id', $restaurante->id)->where('estado', 'en_camino')->count(),
        ];

        return view('restaurante.pedidos.index', compact('pedidos', 'contadores'));
    }

    public function show($id)
    {
        $restaurante = auth()->user()->restaurante;

        $pedido = Pedido::where('restaurante_id', $restaurante->id)
            ->with(['detalles', 'usuario', 'repartidor'])
            ->findOrFail($id);

        return view('restaurante.pedidos.show', compact('pedido'));
    }

    public function cambiarEstado(Request $request, $id)
    {
        $restaurante = auth()->user()->restaurante;

        $pedido = Pedido::where('restaurante_id', $restaurante->id)->findOrFail($id);

        $request->validate([
            'estado' => 'required|in:confirmado,preparando,listo,cancelado'
        ]);

        $pedido->update([
            'estado' => $request->estado,
            'tiempo_preparacion' => $request->tiempo_preparacion ?? null,
        ]);

        // Aquí se enviarían notificaciones al cliente

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'nuevo_estado' => $pedido->estado
        ]);
    }

    public function rechazar(Request $request, $id)
    {
        $restaurante = auth()->user()->restaurante;

        $pedido = Pedido::where('restaurante_id', $restaurante->id)->findOrFail($id);

        $pedido->update([
            'estado' => 'cancelado',
            'cancelado_por' => 'restaurante',
            'motivo_cancelacion' => $request->motivo,
        ]);

        return redirect()->route('restaurante.pedidos.index')
            ->with('success', 'Pedido rechazado');
    }
}