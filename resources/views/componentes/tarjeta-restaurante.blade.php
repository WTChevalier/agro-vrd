{{-- Tarjeta de Restaurante --}}
<article class="bg-white rounded-xl shadow-sm overflow-hidden group hover:shadow-md transition-shadow">
    <a href="{{ route('restaurantes.mostrar', $restaurante) }}" class="block">
        {{-- Imagen --}}
        <div class="relative aspect-[16/10] overflow-hidden">
            <img src="{{ $restaurante->imagenPrincipal?->url ?? asset('images/placeholder-restaurant.jpg') }}"
                 alt="{{ $restaurante->nombre }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

            {{-- Badge de estado --}}
            @if($restaurante->estaAbiertoAhora())
                <span class="absolute top-3 left-3 bg-green-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
                    Abierto
                </span>
            @else
                <span class="absolute top-3 left-3 bg-gray-500 text-white text-xs font-semibold px-2 py-1 rounded-full">
                    Cerrado
                </span>
            @endif

            {{-- Badge destacado --}}
            @if($restaurante->destacado)
                <span class="absolute top-3 right-3 bg-yellow-400 text-gray-900 text-xs font-semibold px-2 py-1 rounded-full">
                    ⭐ Destacado
                </span>
            @endif

            {{-- Botón favorito --}}
            @auth
                <button type="button"
                        class="absolute bottom-3 right-3 w-8 h-8 bg-white/90 hover:bg-white rounded-full flex items-center justify-center shadow-sm transition-colors favorito-btn"
                        data-tipo="restaurante"
                        data-id="{{ $restaurante->id }}">
                    <svg class="w-5 h-5 {{ $restaurante->esFavoritoDeUsuario(auth()->id()) ? 'text-red-500 fill-current' : 'text-gray-600' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>
            @endauth
        </div>

        {{-- Contenido --}}
        <div class="p-4">
            {{-- Nombre y calificación --}}
            <div class="flex justify-between items-start mb-2">
                <h3 class="font-semibold text-gray-900 text-lg group-hover:text-orange-500 transition-colors line-clamp-1">
                    {{ $restaurante->nombre }}
                </h3>
                @if($restaurante->calificacion > 0)
                    <div class="flex items-center bg-green-100 px-2 py-0.5 rounded-md">
                        <span class="text-green-700 font-semibold text-sm">{{ number_format($restaurante->calificacion, 1) }}</span>
                        <svg class="w-3.5 h-3.5 text-green-600 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Tipos de cocina --}}
            <p class="text-gray-500 text-sm mb-3 line-clamp-1">
                {{ $restaurante->tiposCocina->pluck('nombre')->implode(' • ') }}
            </p>

            {{-- Info de delivery --}}
            <div class="flex items-center gap-4 text-sm text-gray-600">
                @if($restaurante->ofrece_delivery)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $restaurante->tiempo_entrega_estimado ?? '30-45' }} min
                    </div>
                @endif

                @if($restaurante->costo_delivery > 0)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        RD${{ number_format($restaurante->costo_delivery, 0) }}
                    </div>
                @elseif($restaurante->ofrece_delivery)
                    <span class="text-green-600 font-medium">Delivery gratis</span>
                @endif

                @if($restaurante->pedido_minimo > 0)
                    <span class="text-gray-500">
                        Mín. RD${{ number_format($restaurante->pedido_minimo, 0) }}
                    </span>
                @endif
            </div>

            {{-- Distancia (si está disponible) --}}
            @if(isset($restaurante->distancia))
                <div class="mt-2 text-sm text-gray-500">
                    <span class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        A {{ number_format($restaurante->distancia, 1) }} km
                    </span>
                </div>
            @endif
        </div>
    </a>
</article>
