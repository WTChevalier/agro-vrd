<?php

namespace App\Http\Controllers\Restaurante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Producto;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $restaurante = auth()->user()->restaurante;

        // Estadísticas del día
        $hoy = Carbon::today();

        $pedidosHoy = Pedido::where('restaurante_id', $restaurante->id)
            ->whereDate('created_at', $hoy)
            ->count();

        $ventasHoy = Pedido::where('restaurante_id', $restaurante->id)
            ->whereDate('created_at', $hoy)
            ->whereIn('estado', ['entregado', 'en_camino', 'preparando'])
            ->sum('total');

        $pedidosPendientes = Pedido::where('restaurante_id', $restaurante->id)
            ->whereIn('estado', ['pendiente', 'confirmado'])
            ->count();

        // Pedidos recientes
        $pedidosRecientes = Pedido::where('restaurante_id', $restaurante->id)
            ->with(['detalles', 'usuario'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // Productos más vendidos
        $productosPopulares = Producto::where('restaurante_id', $restaurante->id)
            ->orderByDesc('total_vendidos')
            ->take(5)
            ->get();

        // Estadísticas de la semana
        $ventasSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $ventasSemana[] = [
                'fecha' => $fecha->format('D'),
                'total' => Pedido::where('restaurante_id', $restaurante->id)
                    ->whereDate('created_at', $fecha)
                    ->whereIn('estado', ['entregado'])
                    ->sum('total')
            ];
        }

        return view('restaurante.dashboard', compact(
            'restaurante',
            'pedidosHoy',
            'ventasHoy',
            'pedidosPendientes',
            'pedidosRecientes',
            'productosPopulares',
            'ventasSemana'
        ));
    }
}