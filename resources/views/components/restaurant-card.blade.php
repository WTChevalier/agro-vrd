@props(['restaurant', 'showDistance' => false])

<a href="{{ route('restaurants.show', $restaurant->slug) }}"
   class="group bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition duration-300">
    <!-- Image -->
    <div class="relative h-48 overflow-hidden">
        <img src="{{ $restaurant->cover_image_url }}"
             alt="{{ $restaurant->name }}"
             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">

        <!-- Status Badge -->
        @if($restaurant->is_open)
            <span class="absolute top-3 left-3 bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full">
                Abierto
            </span>
        @else
            <span class="absolute top-3 left-3 bg-gray-500 text-white text-xs font-medium px-2 py-1 rounded-full">
                Cerrado
            </span>
        @endif

        <!-- Featured Badge -->
        @if($restaurant->is_featured)
            <span class="absolute top-3 right-3 bg-amber-500 text-white text-xs font-medium px-2 py-1 rounded-full">
                ⭐ Destacado
            </span>
        @endif

        <!-- Favorite Button -->
        @auth
            <button class="absolute bottom-3 right-3 p-2 bg-white/90 rounded-full hover:bg-white transition"
                    onclick="event.preventDefault(); toggleFavorite('restaurant', {{ $restaurant->id }})">
                <svg class="w-5 h-5 {{ $restaurant->is_favorited ? 'text-red-500 fill-current' : 'text-gray-400' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        @endauth
    </div>

    <!-- Content -->
    <div class="p-4">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition">
                    {{ $restaurant->name }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $restaurant->cuisine_types_text }}
                </p>
            </div>
            @if($restaurant->rating > 0)
                <div class="flex items-center bg-green-100 px-2 py-1 rounded">
                    <span class="text-green-700 font-medium text-sm">{{ number_format($restaurant->rating, 1) }}</span>
                    <svg class="w-4 h-4 text-green-600 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            @endif
        </div>

        <!-- Info Row -->
        <div class="flex items-center text-sm text-gray-500 mt-3 space-x-3">
            <!-- Delivery Time -->
            <span class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $restaurant->preparation_time }}-{{ $restaurant->preparation_time + 15 }} min
            </span>

            <!-- Delivery Fee -->
            @if($restaurant->accepts_delivery)
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    @if($restaurant->delivery_fee > 0)
                        RD$ {{ number_format($restaurant->delivery_fee, 0) }}
                    @else
                        Gratis
                    @endif
                </span>
            @endif

            <!-- Distance -->
            @if($showDistance && isset($restaurant->distance))
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ number_format($restaurant->distance, 1) }} km
                </span>
            @endif
        </div>

        <!-- Minimum Order -->
        @if($restaurant->minimum_order > 0)
            <p class="text-xs text-gray-400 mt-2">
                Pedido mínimo: RD$ {{ number_format($restaurant->minimum_order, 0) }}
            </p>
        @endif
    </div>
</a>
