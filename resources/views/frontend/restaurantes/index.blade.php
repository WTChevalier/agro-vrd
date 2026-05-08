@extends('layouts.app')

@section('title', 'Restaurantes - SazónRD')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form action="{{ route('restaurantes.index') }}" method="GET" class="flex flex-wrap gap-4 items-center">
            <select name="categoria" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sazon-primary">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->slug }}" {{ request('categoria') == $cat->slug ? 'selected' : '' }}>
                        {{ $cat->nombre }}
                    </option>
                @endforeach
            </select>

            <select name="orden" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sazon-primary">
                <option value="recomendados" {{ request('orden') == 'recomendados' ? 'selected' : '' }}>Recomendados</option>
                <option value="calificacion" {{ request('orden') == 'calificacion' ? 'selected' : '' }}>Mejor calificación</option>
                <option value="tiempo" {{ request('orden') == 'tiempo' ? 'selected' : '' }}>Más rápido</option>
            </select>

            <label class="flex items-center">
                <input type="checkbox" name="delivery_gratis" value="1" {{ request('delivery_gratis') ? 'checked' : '' }}
                       class="form-checkbox h-5 w-5 text-sazon-primary rounded">
                <span class="ml-2 text-gray-700">Delivery gratis</span>
            </label>

            <button type="submit" class="bg-sazon-primary text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-filter mr-2"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- Resultados -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">{{ $titulo ?? 'Restaurantes' }}</h1>
        <span class="text-gray-500">{{ $restaurantes->total() }} restaurantes</span>
    </div>

    @if($restaurantes->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($restaurantes as $restaurante)
                <a href="{{ route('restaurantes.show', $restaurante->slug) }}"
                   class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition group">
                    <div class="relative h-40 overflow-hidden">
                        <img src="{{ $restaurante->imagen_portada ?? 'https://via.placeholder.com/400x200?text=Restaurante' }}"
                             alt="{{ $restaurante->nombre }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">

                        @if(!$restaurante->abierto)
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="text-white font-bold">Cerrado</span>
                            </div>
                        @endif

                        @if($restaurante->tiene_promocion)
                            <span class="absolute top-2 left-2 bg-sazon-accent text-white text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-tag mr-1"></i> Promoción
                            </span>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 mb-1">{{ $restaurante->nombre }}</h3>
                        <p class="text-sm text-gray-500 mb-2 truncate">
                            {{ $restaurante->categorias->pluck('nombre')->implode(', ') }}
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span class="font-medium">{{ number_format($restaurante->calificacion_promedio, 1) }}</span>
                            </div>
                            <div class="text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $restaurante->tiempo_entrega_estimado }} min
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-sm">
                            <span class="text-gray-500">
                                <i class="fas fa-motorcycle mr-1"></i>
                                @if($restaurante->costo_delivery == 0)
                                    <span class="text-green-600 font-medium">Gratis</span>
                                @else
                                    RD$ {{ number_format($restaurante->costo_delivery, 0) }}
                                @endif
                            </span>
                            <span class="text-gray-500">
                                Mín. RD$ {{ number_format($restaurante->pedido_minimo, 0) }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $restaurantes->withQueryString()->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos restaurantes</h3>
            <p class="text-gray-500 mb-4">Intenta ajustar los filtros</p>
            <a href="{{ route('restaurantes.index') }}" class="text-sazon-primary hover:underline">
                Ver todos los restaurantes
            </a>
        </div>
    @endif
</div>
@endsection