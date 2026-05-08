<x-layouts.app>
    @push('titulo', 'Inicio')

    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-r from-orange-500 to-red-500 overflow-hidden">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 relative z-10">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                    El sabor de <span class="text-yellow-300">República Dominicana</span><br>
                    en tu puerta
                </h1>
                <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                    Descubre los mejores restaurantes dominicanos y recibe tu comida favorita en minutos.
                </p>

                {{-- Barra de búsqueda --}}
                <div class="max-w-2xl mx-auto">
                    <form action="{{ route('buscar') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" placeholder="¿Qué se te antoja hoy?"
                                   class="w-full pl-12 pr-4 py-4 rounded-full text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-yellow-400 text-lg">
                        </div>
                        <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold px-8 py-4 rounded-full transition-colors">
                            Buscar
                        </button>
                    </form>
                </div>

                {{-- Tags populares --}}
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <span class="text-white/70 text-sm">Popular:</span>
                    @foreach(['Mangú', 'Sancocho', 'Mofongo', 'Chicharrón', 'Arroz con Pollo'] as $tag)
                        <a href="{{ route('buscar', ['q' => $tag]) }}"
                           class="bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-full text-sm transition-colors">
                            {{ $tag }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Onda decorativa --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#F9FAFB"/>
            </svg>
        </div>
    </section>

    {{-- Tipos de cocina --}}
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Explora por tipo de cocina</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-10 gap-4">
                @foreach($tiposCocinaPopulares as $tipo)
                    <a href="{{ route('restaurantes.por-tipo-cocina', $tipo) }}"
                       class="flex flex-col items-center p-4 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2 group-hover:bg-orange-200 transition-colors">
                            <span class="text-2xl">{{ $tipo->icono ?? '🍽️' }}</span>
                        </div>
                        <span class="text-sm font-medium text-gray-700 text-center">{{ $tipo->nombre }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Restaurantes destacados --}}
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Restaurantes destacados</h2>
                <a href="{{ route('restaurantes.index') }}" class="text-orange-500 hover:text-orange-600 font-medium">
                    Ver todos →
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($restaurantesDestacados as $restaurante)
                    @include('componentes.tarjeta-restaurante', ['restaurante' => $restaurante])
                @endforeach
            </div>
        </div>
    </section>

    {{-- Restaurantes cercanos (si hay ubicación) --}}
    @if($restaurantesCercanos->isNotEmpty())
        <section class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Cerca de ti</h2>
                        <p class="text-gray-600 text-sm">Restaurantes a menos de 5 km</p>
                    </div>
                    <a href="{{ route('restaurantes.cerca-de-mi') }}" class="text-orange-500 hover:text-orange-600 font-medium">
                        Ver más →
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($restaurantesCercanos as $restaurante)
                        @include('componentes.tarjeta-restaurante', ['restaurante' => $restaurante])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Mejor calificados --}}
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Los mejor calificados</h2>
                    <p class="text-gray-600 text-sm">Restaurantes con las mejores reseñas</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($mejorCalificados as $restaurante)
                    @include('componentes.tarjeta-restaurante', ['restaurante' => $restaurante])
                @endforeach
            </div>
        </div>
    </section>

    {{-- Nuevos restaurantes --}}
    @if($restaurantesNuevos->isNotEmpty())
        <section class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2">
                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">Nuevo</span>
                        <h2 class="text-2xl font-bold text-gray-900">Recién llegados</h2>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($restaurantesNuevos as $restaurante)
                        @include('componentes.tarjeta-restaurante', ['restaurante' => $restaurante])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Cómo funciona --}}
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">¿Cómo funciona SazónRD?</h2>
                <p class="text-gray-600">Pedir tu comida favorita es muy fácil</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🔍</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">1. Encuentra tu restaurante</h3>
                    <p class="text-gray-600">Busca entre cientos de restaurantes dominicanos cerca de ti</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🛒</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">2. Elige tu comida</h3>
                    <p class="text-gray-600">Explora el menú y agrega tus platos favoritos al carrito</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🚴</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">3. Recibe en tu puerta</h3>
                    <p class="text-gray-600">Tu pedido llega caliente y fresco directamente a ti</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA para restaurantes --}}
    <section class="py-16 bg-gradient-to-r from-gray-900 to-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <h2 class="text-3xl font-bold text-white mb-4">¿Tienes un restaurante?</h2>
                    <p class="text-gray-300 mb-6">Únete a SazónRD y lleva tu negocio al siguiente nivel. Aumenta tus ventas y alcanza más clientes.</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('registrar-restaurante') }}"
                           class="inline-flex items-center justify-center px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-full transition-colors">
                            Registrar mi restaurante
                        </a>
                        <a href="{{ route('ser-repartidor') }}"
                           class="inline-flex items-center justify-center px-6 py-3 border border-white text-white hover:bg-white hover:text-gray-900 font-semibold rounded-full transition-colors">
                            Ser repartidor
                        </a>
                    </div>
                </div>
                <div class="hidden md:block">
                    <img src="{{ asset('images/restaurant-owner.svg') }}" alt="Dueño de restaurante" class="w-full max-w-md mx-auto">
                </div>
            </div>
        </div>
    </section>

    {{-- Descargar App (futuro) --}}
    <section class="py-12 bg-orange-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Próximamente en tu celular</h2>
            <p class="text-gray-600 mb-6">Descarga nuestra app y pide desde cualquier lugar</p>
            <div class="flex justify-center gap-4">
                <div class="bg-gray-300 text-gray-500 px-6 py-3 rounded-lg cursor-not-allowed">
                    <span class="text-sm">Próximamente en</span>
                    <div class="font-semibold">App Store</div>
                </div>
                <div class="bg-gray-300 text-gray-500 px-6 py-3 rounded-lg cursor-not-allowed">
                    <span class="text-sm">Próximamente en</span>
                    <div class="font-semibold">Google Play</div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
