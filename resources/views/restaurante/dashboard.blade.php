@extends('restaurante.layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-receipt text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Pedidos Hoy</p>
                <p class="text-2xl font-bold">{{ $pedidosHoy }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-dollar-sign text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Ventas Hoy</p>
                <p class="text-2xl font-bold">RD$ {{ number_format($ventasHoy, 0) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-clock text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Pendientes</p>
                <p class="text-2xl font-bold">{{ $pedidosPendientes }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-star text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Calificación</p>
                <p class="text-2xl font-bold">{{ number_format($restaurante->calificacion_promedio, 1) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Pedidos Recientes -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Pedidos Recientes</h3>
            <a href="{{ route('restaurante.pedidos.index') }}" class="text-blue-600 text-sm hover:underline">Ver todos</a>
        </div>
        <div class="p-4">
            @forelse($pedidosRecientes as $pedido)
                <div class="flex items-center justify-between py-3 border-b last:border-0">
                    <div>
                        <p class="font-medium">{{ $pedido->codigo }}</p>
                        <p class="text-sm text-gray-500">{{ $pedido->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium">RD$ {{ number_format($pedido->total, 0) }}</p>
                        <span class="text-xs px-2 py-1 rounded-full
                            @switch($pedido->estado)
                                @case('pendiente') bg-yellow-100 text-yellow-800 @break
                                @case('confirmado') bg-blue-100 text-blue-800 @break
                                @case('preparando') bg-orange-100 text-orange-800 @break
                                @default bg-gray-100 text-gray-800
                            @endswitch">
                            {{ ucfirst($pedido->estado) }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No hay pedidos</p>
            @endforelse
        </div>
    </div>

    <!-- Productos Populares -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <h3 class="font-semibold text-gray-800">Productos Más Vendidos</h3>
        </div>
        <div class="p-4">
            @forelse($productosPopulares as $producto)
                <div class="flex items-center justify-between py-3 border-b last:border-0">
                    <div class="flex items-center">
                        <img src="{{ $producto->imagen ?? 'https://via.placeholder.com/40' }}"
                             class="w-10 h-10 rounded object-cover mr-3">
                        <div>
                            <p class="font-medium">{{ $producto->nombre }}</p>
                            <p class="text-sm text-gray-500">RD$ {{ number_format($producto->precio, 0) }}</p>
                        </div>
                    </div>
                    <span class="text-gray-600">{{ $producto->total_vendidos }} vendidos</span>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No hay datos</p>
            @endforelse
        </div>
    </div>
</div>
@endsection