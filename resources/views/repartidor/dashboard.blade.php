@extends('repartidor.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-4">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <i class="fas fa-box text-3xl text-green-500 mb-2"></i>
            <p class="text-2xl font-bold">{{ $entregasHoy }}</p>
            <p class="text-gray-500 text-sm">Entregas Hoy</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <i class="fas fa-dollar-sign text-3xl text-green-500 mb-2"></i>
            <p class="text-2xl font-bold">RD$ {{ number_format($gananciasHoy, 0) }}</p>
            <p class="text-gray-500 text-sm">Ganado Hoy</p>
        </div>
    </div>

    <!-- Pedido Activo -->
    @if($pedidoActivo)
        <div class="bg-green-50 border-2 border-green-500 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <span class="font-bold text-green-700">Pedido en Curso</span>
                <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs">En camino</span>
            </div>
            <div class="mb-3">
                <p class="font-semibold">{{ $pedidoActivo->restaurante->nombre }}</p>
                <p class="text-sm text-gray-600">{{ $pedidoActivo->codigo }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 mb-3">
                <p class="text-sm text-gray-500">Entregar en:</p>
                <p class="font-medium">{{ $pedidoActivo->direccion_entrega }}</p>
            </div>
            <a href="{{ route('repartidor.pedido.activo') }}"
               class="block w-full bg-green-600 text-white text-center py-3 rounded-lg font-semibold">
                Ver Detalles
            </a>
        </div>
    @endif

    <!-- Pedidos Disponibles -->
    <div class="mb-4">
        <h2 class="text-lg font-semibold mb-3">Pedidos Disponibles</h2>

        @forelse($pedidosDisponibles as $pedido)
            <div class="bg-white rounded-xl shadow mb-3 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold">{{ $pedido->restaurante->nombre }}</p>
                        <p class="text-sm text-gray-500">{{ $pedido->codigo }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-clock mr-1"></i>
                            {{ $pedido->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-600">
                            RD$ {{ number_format($pedido->comision_repartidor ?? 50, 0) }}
                        </p>
                        <p class="text-xs text-gray-500">Comisión</p>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t">
                    <div class="flex items-center text-sm text-gray-600 mb-2">
                        <i class="fas fa-store text-orange-500 mr-2"></i>
                        <span>{{ $pedido->restaurante->direccion }}</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                        <span>{{ Str::limit($pedido->direccion_entrega, 40) }}</span>
                    </div>
                </div>

                <form action="{{ route('repartidor.aceptar-pedido', $pedido->id) }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">
                        <i class="fas fa-check mr-2"></i> Aceptar Pedido
                    </button>
                </form>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow p-8 text-center">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No hay pedidos disponibles</p>
                <p class="text-sm text-gray-400">Los nuevos pedidos aparecerán aquí</p>
            </div>
        @endforelse
    </div>
</div>
@endsection