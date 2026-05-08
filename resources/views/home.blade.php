<x-app-layout>
    <x-slot name="title">{{ config('app.name') }} - El sabor de República Dominicana</x-slot>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-amber-500 to-orange-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">
                        El sabor de República Dominicana en tu puerta
                    </h1>
                    <p class="text-lg md:text-xl text-amber-100 mb-8">
                        Descubre los mejores restaurantes y disfruta de la auténtica gastronomía dominicana desde la comodidad de tu hogar.
                    </p>

                    <!-- Search Form -->
                    <form action="{{ route('search') }}" method="GET" class="flex">
                        <input type="text" name="q"
                               placeholder="¿Qué se te antoja hoy?"
                               class="flex-1 px-6 py-4 rounded-l-full text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-300">
                        <button type="submit" class="bg-gray-900 hover:bg-gray-800 px-8 py-4 rounded-r-full font-medium transition">
                            Buscar
                        </button>
                    </form>

                    <!-- Quick Categories -->
                    <div class="flex flex-wrap gap-2 mt-6">
                        @foreach(['Dominicana', 'Pizzería', 'Mariscos', 'Carnes', 'Sushi'] as $category)
                            <a href="{{ route('restaurants.category', Str::slug($category)) }}"
                               class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-full text-sm transition">
                                {{ $category }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="hidden md:block">
                    <img src="{{ asset('images/hero-food.png') }}" alt="Comida dominicana" class="w-full max-w-lg mx-auto">
                </div>
            </div>
        </div>

        <!-- Wave decoration -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" class="w-full h-auto fill-gray-50">
                <path d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z"></path>
            </svg>
        </div>
    </section>

    <!-- Featured Restaurants -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Restaurantes Destacados</h2>
                    <p class="text-gray-600">Los mejores lugares para comer en República Dominicana</p>
                </div>
                <a href="{{ route('restaurants.index') }}" class="text-amber-600 hover:text-amber-700 font-medium">
                    Ver todos →
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse($featuredRestaurants ?? [] as $restaurant)
                    <x-restaurant-card :restaurant="$restaurant" />
                @empty
                    <p class="col-span-4 text-center text-gray-500 py-8">No hay restaurantes destacados disponibles.</p>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Nearby Restaurants -->
    @if(isset($nearbyRestaurants) && $nearbyRestaurants->count() > 0)
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Cerca de Ti</h2>
                    <p class="text-gray-600">Restaurantes en tu zona</p>
                </div>
                <a href="{{ route('restaurants.nearby') }}" class="text-amber-600 hover:text-amber-700 font-medium">
                    Ver más →
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($nearbyRestaurants as $restaurant)
                    <x-restaurant-card :restaurant="$restaurant" :showDistance="true" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Cuisine Types -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 text-center mb-8">
                ¿Qué tipo de comida quieres?
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                @php
                    $cuisines = [
                        ['name' => 'Dominicana', 'icon' => '🍚', 'slug' => 'dominicana'],
                        ['name' => 'Italiana', 'icon' => '🍕', 'slug' => 'italiana'],
                        ['name' => 'Mariscos', 'icon' => '🦐', 'slug' => 'mariscos'],
                        ['name' => 'Carnes', 'icon' => '🥩', 'slug' => 'carnes'],
                        ['name' => 'Sushi', 'icon' => '🍣', 'slug' => 'sushi'],
                        ['name' => 'Fast Food', 'icon' => '🍔', 'slug' => 'fast-food'],
                    ];
                @endphp

                @foreach($cuisines as $cuisine)
                    <a href="{{ route('restaurants.category', $cuisine['slug']) }}"
                       class="flex flex-col items-center p-6 bg-gray-50 rounded-xl hover:bg-amber-50 hover:shadow-md transition group">
                        <span class="text-4xl mb-2">{{ $cuisine['icon'] }}</span>
                        <span class="font-medium text-gray-900 group-hover:text-amber-600">{{ $cuisine['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Popular Dishes -->
    @if(isset($popularDishes) && $popularDishes->count() > 0)
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8">Platos Populares</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($popularDishes as $dish)
                    <x-dish-card :dish="$dish" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- How it Works -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 text-center mb-12">
                ¿Cómo funciona?
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🔍</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">1. Encuentra tu restaurante</h3>
                    <p class="text-gray-600">Explora cientos de restaurantes y encuentra tu comida favorita.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🛒</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">2. Haz tu pedido</h3>
                    <p class="text-gray-600">Agrega tus platos favoritos al carrito y personaliza tu orden.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🚀</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">3. Recibe en tu puerta</h3>
                    <p class="text-gray-600">Rastrea tu pedido en tiempo real y disfruta de tu comida.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA: Download App -->
    <section class="py-16 bg-gradient-to-r from-amber-500 to-orange-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl md:text-3xl font-bold mb-4">
                Próximamente: App Móvil
            </h2>
            <p class="text-amber-100 mb-8 max-w-2xl mx-auto">
                Descarga nuestra app y lleva SazónRD contigo a todas partes. Recibe ofertas exclusivas y haz pedidos más rápido.
            </p>
            <div class="flex justify-center gap-4">
                <a href="#" class="bg-black hover:bg-gray-900 px-6 py-3 rounded-lg flex items-center transition">
                    <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                    </svg>
                    App Store
                </a>
                <a href="#" class="bg-black hover:bg-gray-900 px-6 py-3 rounded-lg flex items-center transition">
                    <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 01-.61-.92V2.734a1 1 0 01.609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 010 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.802 8.99l-2.303 2.303-8.635-8.635z"/>
                    </svg>
                    Google Play
                </a>
            </div>
        </div>
    </section>

</x-app-layout>
