@extends('layouts.app')

@section('title', 'Mis Pedidos - SazónRD')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-receipt mr-2"></i> Mis Pedidos
    </h1>

    @if($pedidos->count() > 0)
        <div class="space-y-4">
            @foreach($pedidos as $pedido)
                <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="font-bold text-sazon-primary">{{ $pedido->codigo }}</span>
                            <span class="text-gray-500 text-sm ml-2">
                                {{ $pedido->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            @switch($pedido->estado)
                                @case('pendiente') bg-yellow-100 text-yellow-800 @break
                                @case('confirmado') bg-blue-100 text-blue-800 @break
                                @case('preparando') bg-orange-100 text-orange-800 @break
                                @case('en_camino') bg-purple-100 text-purple-800 @break
                                @case('entregado') bg-green-100 text-green-800 @break
                                @case('cancelado') bg-red-100 text-red-800 @break
                                @default bg-gray-100 text-gray-800
                            @endswitch">
                            {{ ucfirst(str_replace('_', ' ', $pedido->estado)) }}
                        </span>
                    </div>

                    <div class="flex items-center gap-4">
                        <img src="{{ $pedido->restaurante->imagen_portada ?? 'https://via.placeholder.com/60' }}"
                             class="w-16 h-16 rounded-lg object-cover">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">{{ $pedido->restaurante->nombre }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ $pedido->detalles->count() }} productos •
                                RD$ {{ number_format($pedido->total, 0) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <a href="{{ route('pedidos.show', $pedido->codigo) }}"
                               class="text-sazon-primary hover:underline text-sm">
                                Ver detalles <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                            @if(in_array($pedido->estado, ['en_camino', 'preparando']))
                                <br>
                                <a href="{{ route('pedidos.seguimiento', $pedido->codigo) }}"
                                   class="text-green-600 hover:underline text-sm">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Seguir
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $pedidos->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-receipt text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No tienes pedidos</h3>
            <p class="text-gray-500 mb-4">¡Haz tu primer pedido!</p>
            <a href="{{ route('restaurantes.index') }}"
               class="inline-block bg-sazon-primary text-white px-6 py-3 rounded-lg hover:bg-red-600 transition">
                Ver Restaurantes
            </a>
        </div>
    @endif
</div>
@endsection