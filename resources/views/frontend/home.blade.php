@extends('layouts.app')

@section('title', 'SazónRD - Delivery de Comida Dominicana')

@section('content')
<!-- Hero Section -->
<section class="gradient-sazon text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    El Sabor de República Dominicana en tu Puerta
                </h1>
                <p class="text-xl mb-8 text-white/90">
                    Descubre los mejores restaurantes dominicanos. Mangú, mofongo, sancocho y mucho más.
                </p>
                <form action="{{ route('buscar') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <i class="fas fa-map-marker-alt absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="ubicacion" placeholder="Tu dirección de entrega..."
                               class="w-full pl-12 pr-4 py-4 rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-sazon-accent">
                    </div>
                    <button type="submit" class="bg-sazon-secondary text-white px-8 py-4 rounded-lg font-semibold hover:bg-opacity-90 transition">
                        <i class="fas fa-search mr-2"></i> Buscar
                    </button>
                </form>
            </div>
            <div class="hidden md:block">
                <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600" alt="Comida Dominicana"
                     class="rounded-lg shadow-2xl transform rotate-3 hover:rotate-0 transition duration-300">
            </div>
        </div>
    </div>
</section>

<!-- Categorías -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Explora por Categoría</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
            @foreach($categorias as $categoria)
                <a href="{{ route('restaurantes.index', ['categoria' => $categoria->slug]) }}"
                   class="flex flex-col items-center p-4 bg-gray-50 rounded-xl hover:bg-sazon-light hover:shadow-md transition group">
                    <div class="w-16 h-16 bg-sazon-primary/10 rounded-full flex items-center justify-center mb-2 group-hover:bg-sazon-primary/20">
                        <i class="{{ $categoria->icono ?? 'fas fa-utensils' }} text-2xl text-sazon-primary"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 text-center">{{ $categoria->nombre }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Restaurantes Destacados -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Restaurantes Destacados</h2>
            <a href="{{ route('restaurantes.index') }}" class="text-sazon-primary hover:underline">
                Ver todos <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($restaurantesDestacados as $restaurante)
                <a href="{{ route('restaurantes.show', $restaurante->slug) }}"
                   class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition group">
                    <div class="relative h-40 overflow-hidden">
                        <img src="{{ $restaurante->imagen_portada ?? 'https://via.placeholder.com/400x200?text=Restaurante' }}"
                             alt="{{ $restaurante->nombre }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @if($restaurante->tiene_promocion)
                            <span class="absolute top-2 left-2 bg-sazon-accent text-white text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-tag mr-1"></i> Promoción
                            </span>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 mb-1">{{ $restaurante->nombre }}</h3>
                        <p class="text-sm text-gray-500 mb-2">
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
                        <div class="flex items-center mt-2 text-sm text-gray-500">
                            <i class="fas fa-motorcycle mr-1"></i>
                            @if($restaurante->costo_delivery == 0)
                                <span class="text-green-600 font-medium">Delivery Gratis</span>
                            @else
                                RD$ {{ number_format($restaurante->costo_delivery, 0) }}
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Promociones -->
@if(isset($promociones) && $promociones->count() > 0)
<section class="py-12 bg-sazon-light">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-fire text-sazon-primary mr-2"></i> Ofertas del Día
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($promociones as $promo)
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="gradient-sazon p-4 text-white">
                        <span class="text-3xl font-bold">{{ $promo->descuento }}% OFF</span>
                        <p class="text-sm opacity-90">{{ $promo->descripcion }}</p>
                    </div>
                    <div class="p-4">
                        <h4 class="font-semibold">{{ $promo->restaurante->nombre }}</h4>
                        <a href="{{ route('restaurantes.show', $promo->restaurante->slug) }}"
                           class="mt-3 inline-block bg-sazon-primary text-white text-sm px-4 py-2 rounded-full hover:bg-red-600 transition">
                            Ver Menú
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Platos Populares -->
@if(isset($platosPopulares) && $platosPopulares->count() > 0)
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Platos Populares</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($platosPopulares as $plato)
                <a href="{{ route('restaurantes.show', $plato->restaurante->slug) }}"
                   class="bg-white rounded-lg shadow hover:shadow-md transition p-3 text-center group">
                    <div class="w-24 h-24 mx-auto mb-2 rounded-full overflow-hidden">
                        <img src="{{ $plato->imagen ?? 'https://via.placeholder.com/100?text=Plato' }}"
                             alt="{{ $plato->nombre }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                    </div>
                    <h4 class="text-sm font-medium text-gray-800 truncate">{{ $plato->nombre }}</h4>
                    <p class="text-xs text-gray-500 truncate">{{ $plato->restaurante->nombre }}</p>
                    <p class="text-sm font-bold text-sazon-primary mt-1">RD$ {{ number_format($plato->precio, 0) }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Cómo Funciona -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-10">¿Cómo Funciona?</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-sazon-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-3xl text-sazon-primary"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">1. Busca</h3>
                <p class="text-gray-600">Explora restaurantes cercanos y encuentra tu comida favorita</p>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-sazon-accent/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shopping-cart text-3xl text-sazon-accent"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">2. Ordena</h3>
                <p class="text-gray-600">Agrega tus platos al carrito y personaliza tu pedido</p>
            </div>
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-motorcycle text-3xl text-green-600"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">3. Disfruta</h3>
                <p class="text-gray-600">Recibe tu pedido en la puerta de tu casa</p>
            </div>
        </div>
    </div>
</section>

<!-- App Download CTA -->
<section class="py-12 gradient-sazon text-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-4">Descarga Nuestra App</h2>
                <p class="text-lg mb-6 opacity-90">
                    Ordena más rápido, rastrea tu pedido en tiempo real y accede a ofertas exclusivas.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center hover:bg-gray-800 transition">
                        <i class="fab fa-apple text-3xl mr-3"></i>
                        <div>
                            <div class="text-xs">Descargar en</div>
                            <div class="text-lg font-semibold">App Store</div>
                        </div>
                    </a>
                    <a href="#" class="bg-black text-white px-6 py-3 rounded-lg flex items-center hover:bg-gray-800 transition">
                        <i class="fab fa-google-play text-3xl mr-3"></i>
                        <div>
                            <div class="text-xs">Disponible en</div>
                            <div class="text-lg font-semibold">Google Play</div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="hidden md:block text-center">
                <i class="fas fa-mobile-alt text-9xl opacity-50"></i>
            </div>
        </div>
    </div>
</section>
@endsection