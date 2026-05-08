@extends('layouts.app')

@section('title', 'Seguimiento - ' . $pedido->codigo)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="gradient-sazon text-white p-6 text-center">
            <h1 class="text-2xl font-bold mb-2">Seguimiento de Pedido</h1>
            <p class="text-lg">{{ $pedido->codigo }}</p>
        </div>

        <!-- Estado actual -->
        <div class="p-6">
            <!-- Timeline -->
            <div class="relative">
                @php
                    $estados = ['pendiente', 'confirmado', 'preparando', 'en_camino', 'entregado'];
                    $estadoActual = array_search($pedido->estado, $estados);
                @endphp

                <div class="flex justify-between mb-8">
                    @foreach($estados as $index => $estado)
                        <div class="flex flex-col items-center {{ $index <= $estadoActual ? 'text-sazon-primary' : 'text-gray-300' }}">
                            <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center mb-2
                                {{ $index <= $estadoActual ? 'border-sazon-primary bg-sazon-primary text-white' : 'border-gray-300' }}">
                                @switch($estado)
                                    @case('pendiente')
                                        <i class="fas fa-clock"></i>
                                        @break
                                    @case('confirmado')
                                        <i class="fas fa-check"></i>
                                        @break
                                    @case('preparando')
                                        <i class="fas fa-utensils"></i>
                                        @break
                                    @case('en_camino')
                                        <i class="fas fa-motorcycle"></i>
                                        @break
                                    @case('entregado')
                                        <i class="fas fa-home"></i>
                                        @break
                                @endswitch
                            </div>
                            <span class="text-xs text-center">{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
                        </div>
                    @endforeach
                </div>

                <!-- Línea de progreso -->
                <div class="absolute top-5 left-0 right-0 h-0.5 bg-gray-200 -z-10" style="margin: 0 40px;">
                    <div class="h-full bg-sazon-primary transition-all duration-500"
                         style="width: {{ ($estadoActual / 4) * 100 }}%"></div>
                </div>
            </div>

            <!-- Info del repartidor -->
            @if($pedido->repartidor && $pedido->estado === 'en_camino')
                <div class="bg-sazon-light rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Tu repartidor</h3>
                    <div class="flex items-center gap-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($pedido->repartidor->nombre) }}&background=E63946&color=fff"
                             class="w-14 h-14 rounded-full">
                        <div>
                            <p class="font-medium">{{ $pedido->repartidor->nombre }}</p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-phone mr-1"></i> {{ $pedido->repartidor->telefono }}
                            </p>
                        </div>
                        <a href="tel:{{ $pedido->repartidor->telefono }}"
                           class="ml-auto bg-green-500 text-white px-4 py-2 rounded-full hover:bg-green-600">
                            <i class="fas fa-phone"></i>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Mapa placeholder -->
            <div class="bg-gray-100 rounded-lg h-64 flex items-center justify-center mb-6">
                <div class="text-center text-gray-500">
                    <i class="fas fa-map-marked-alt text-4xl mb-2"></i>
                    <p>Mapa de seguimiento</p>
                    <p class="text-sm">(Integración con Google Maps)</p>
                </div>
            </div>

            <!-- Dirección de entrega -->
            <div class="border-t pt-4">
                <h3 class="font-semibold text-gray-800 mb-2">Entrega en:</h3>
                <p class="text-gray-600">
                    <i class="fas fa-map-marker-alt text-sazon-primary mr-2"></i>
                    {{ $pedido->direccion_entrega }}
                </p>
            </div>

            <!-- Tiempo estimado -->
            <div class="mt-4 text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-gray-600">Tiempo estimado de entrega</p>
                <p class="text-3xl font-bold text-sazon-primary">25-35 min</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh cada 30 segundos
    setTimeout(() => window.location.reload(), 30000);
</script>
@endpush
@endsection