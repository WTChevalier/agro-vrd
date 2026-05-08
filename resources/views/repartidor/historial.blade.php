@extends('repartidor.layouts.app')

@section('title', 'Historial')

@section('content')
<div class="p-4">
    <!-- Resumen -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 mb-6 text-white">
        <p class="text-sm opacity-80">Total Ganado</p>
        <p class="text-3xl font-bold">RD$ {{ number_format($totalGanancias, 0) }}</p>
    </div>

    <!-- Lista de Entregas -->
    <h2 class="font-semibold mb-3">Entregas Realizadas</h2>

    @forelse($pedidos as $pedido)
        <div class="bg-white rounded-xl shadow mb-3 p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $pedido->restaurante->nombre }}</p>
                    <p class="text-sm text-gray-500">{{ $pedido->codigo }}</p>
                    <p class="text-xs text-gray-400">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-green-600">+RD$ {{ number_format($pedido->comision_repartidor ?? 50, 0) }}</p>
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Entregado</span>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow p-8 text-center">
            <i class="fas fa-history text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No hay entregas registradas</p>
        </div>
    @endforelse

    <div class="mt-4">
        {{ $pedidos->links() }}
    </div>
</div>
@endsection