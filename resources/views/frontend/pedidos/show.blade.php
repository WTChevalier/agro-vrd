@extends('layouts.app')

@section('title', 'Pedido ' . $pedido->codigo . ' - SazónRD')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('pedidos.index') }}" class="text-sazon-primary hover:underline mb-4 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Volver a mis pedidos
    </a>

    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pedido {{ $pedido->codigo }}</h1>
                <p class="text-gray-500">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <span class="px-4 py-2 rounded-full text-sm font-medium
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

        <!-- Restaurante -->
        <div class="border-b pb-4 mb-4">
            <h3 class="font-semibold text-gray-700 mb-2">Restaurante</h3>
            <div class="flex items-center gap-3">
                <img src="{{ $pedido->restaurante->imagen_portada ?? 'https://via.placeholder.com/50' }}"
                     class="w-12 h-12 rounded-lg object-cover">
                <div>
                    <p class="font-medium">{{ $pedido->restaurante->nombre }}</p>
                    <p class="text-sm text-gray-500">{{ $pedido->restaurante->direccion }}</p>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="border-b pb-4 mb-4">
            <h3 class="font-semibold text-gray-700 mb-3">Productos</h3>
            <div class="space-y-3">
                @foreach($pedido->detalles as $detalle)
                    <div class="flex justify-between">
                        <div>
                            <span class="font-medium">{{ $detalle->cantidad }}x</span>
                            {{ $detalle->nombre_producto }}
                            @if($detalle->notas)
                                <p class="text-sm text-gray-500">{{ $detalle->notas }}</p>
                            @endif
                        </div>
                        <span>RD$ {{ number_format($detalle->subtotal, 0) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Dirección -->
        <div class="border-b pb-4 mb-4">
            <h3 class="font-semibold text-gray-700 mb-2">Dirección de entrega</h3>
            <p class="text-gray-600">
                <i class="fas fa-map-marker-alt text-sazon-primary mr-2"></i>
                {{ $pedido->direccion_entrega }}
            </p>
        </div>

        <!-- Totales -->
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span>RD$ {{ number_format($pedido->subtotal, 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Delivery</span>
                <span>RD$ {{ number_format($pedido->costo_delivery, 0) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">ITBIS</span>
                <span>RD$ {{ number_format($pedido->itbis, 0) }}</span>
            </div>
            @if($pedido->descuento > 0)
                <div class="flex justify-between text-sm text-green-600">
                    <span>Descuento</span>
                    <span>-RD$ {{ number_format($pedido->descuento, 0) }}</span>
                </div>
            @endif
            <hr>
            <div class="flex justify-between font-bold text-lg">
                <span>Total</span>
                <span class="text-sazon-primary">RD$ {{ number_format($pedido->total, 0) }}</span>
            </div>
        </div>

        <!-- Método de pago -->
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">Método de pago:</span>
            <span class="font-medium ml-2">{{ ucfirst($pedido->metodo_pago) }}</span>
        </div>

        <!-- Acciones -->
        @if(in_array($pedido->estado, ['preparando', 'en_camino']))
            <a href="{{ route('pedidos.seguimiento', $pedido->codigo) }}"
               class="mt-6 w-full bg-sazon-primary text-white py-3 rounded-lg text-center block hover:bg-red-600 transition font-semibold">
                <i class="fas fa-map-marker-alt mr-2"></i> Seguir Pedido en Tiempo Real
            </a>
        @endif

        @if(in_array($pedido->estado, ['pendiente', 'confirmado']))
            <form action="{{ route('pedidos.cancelar', $pedido->codigo) }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="w-full border border-red-500 text-red-500 py-2 rounded-lg hover:bg-red-50 transition"
                        onclick="return confirm('¿Cancelar este pedido?')">
                    <i class="fas fa-times mr-2"></i> Cancelar Pedido
                </button>
            </form>
        @endif
    </div>
</div>
@endsection