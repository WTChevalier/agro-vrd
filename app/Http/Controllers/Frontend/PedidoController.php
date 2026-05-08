<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::where('usuario_id', auth()->id())
            ->with(['restaurante', 'detalles'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('frontend.pedidos.index', compact('pedidos'));
    }

    public function show($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->where('usuario_id', auth()->id())
            ->with(['restaurante', 'detalles', 'repartidor'])
            ->firstOrFail();

        return view('frontend.pedidos.show', compact('pedido'));
    }

    public function seguimiento($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->with(['restaurante', 'repartidor'])
            ->firstOrFail();

        return view('frontend.pedidos.seguimiento', compact('pedido'));
    }

    public function cancelar($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)
            ->where('usuario_id', auth()->id())
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->firstOrFail();

        $pedido->update([
            'estado' => 'cancelado',
            'cancelado_por' => 'cliente',
            'motivo_cancelacion' => request('motivo'),
        ]);

        return redirect()->route('pedidos.index')
            ->with('success', 'Pedido cancelado correctamente');
    }
}