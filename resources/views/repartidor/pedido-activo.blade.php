@extends('repartidor.layouts.app')

@section('title', 'Pedido Activo')

@section('content')
<div class="p-4">
    @if($pedido)
        <!-- Info del Pedido -->
        <div class="bg-white rounded-xl shadow mb-4">
            <div class="p-4 border-b">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-2xl font-bold">{{ $pedido->codigo }}</span>
                        <p class="text-sm text-gray-500">{{ $pedido->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        En camino
                    </span>
                </div>
            </div>

            <!-- Restaurante -->
            <div class="p-4 border-b">
                <h3 class="text-sm font-medium text-gray-500 mb-2">RECOGER EN</h3>
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-store text-orange-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ $pedido->restaurante->nombre }}</p>
                        <p class="text-sm text-gray-600">{{ $pedido->restaurante->direccion }}</p>
                        <a href="tel:{{ $pedido->restaurante->telefono }}" class="text-green-600 text-sm">
                            <i class="fas fa-phone mr-1"></i> {{ $pedido->restaurante->telefono }}
                        </a>
                    </div>
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($pedido->restaurante->direccion) }}"
                       target="_blank" class="bg-blue-500 text-white px-3 py-2 rounded-lg">
                        <i class="fas fa-directions"></i>
                    </a>
                </div>
            </div>

            <!-- Cliente -->
            <div class="p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-2">ENTREGAR EN</h3>
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-map-marker-alt text-red-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ $pedido->usuario->name ?? 'Cliente' }}</p>
                        <p class="text-sm text-gray-600">{{ $pedido->direccion_entrega }}</p>
                        @if($pedido->usuario && $pedido->usuario->telefono)
                            <a href="tel:{{ $pedido->usuario->telefono }}" class="text-green-600 text-sm">
                                <i class="fas fa-phone mr-1"></i> {{ $pedido->usuario->telefono }}
                            </a>
                        @endif
                    </div>
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($pedido->direccion_entrega) }}"
                       target="_blank" class="bg-blue-500 text-white px-3 py-2 rounded-lg">
                        <i class="fas fa-directions"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="bg-white rounded-xl shadow mb-4 p-4">
            <h3 class="font-semibold mb-3">Productos</h3>
            @foreach($pedido->detalles as $detalle)
                <div class="flex justify-between py-2 border-b last:border-0">
                    <span>{{ $detalle->cantidad }}x {{ $detalle->nombre_producto }}</span>
                </div>
            @endforeach
        </div>

        <!-- Método de Pago -->
        <div class="bg-white rounded-xl shadow mb-4 p-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Método de pago</span>
                <span class="font-semibold">{{ ucfirst($pedido->metodo_pago) }}</span>
            </div>
            @if($pedido->metodo_pago === 'efectivo')
                <div class="mt-2 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Cobrar: <strong>RD$ {{ number_format($pedido->total, 0) }}</strong>
                    </p>
                </div>
            @endif
        </div>

        <!-- Notas -->
        @if($pedido->notas)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4">
                <p class="text-sm font-medium text-yellow-800">
                    <i class="fas fa-sticky-note mr-1"></i> Notas del cliente:
                </p>
                <p class="text-yellow-700">{{ $pedido->notas }}</p>
            </div>
        @endif

        <!-- Confirmar Entrega -->
        <form action="{{ route('repartidor.confirmar-entrega', $pedido->id) }}" method="POST">
            @csrf
            <button type="submit" onclick="return confirm('¿Confirmar que el pedido fue entregado?')"
                    class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-700">
                <i class="fas fa-check-circle mr-2"></i> Confirmar Entrega
            </button>
        </form>
    @else
        <div class="bg-white rounded-xl shadow p-8 text-center">
            <i class="fas fa-motorcycle text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No tienes pedidos activos</p>
            <a href="{{ route('repartidor.dashboard') }}" class="text-green-600 hover:underline mt-2 inline-block">
                Ver pedidos disponibles
            </a>
        </div>
    @endif
</div>
@endsection