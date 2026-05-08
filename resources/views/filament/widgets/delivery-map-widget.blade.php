<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-map class="w-5 h-5 text-primary-500" />
                <span>Mapa de Repartidores en Tiempo Real</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center gap-4 text-sm">
                @php
                    $stats = $this->getMapStats();
                @endphp
                <div class="flex items-center gap-1">
                    <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-success-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $stats['drivers_available'] }} disponibles</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-warning-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $stats['drivers_on_delivery'] }} en ruta</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-primary-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $stats['active_deliveries'] }} entregas activas</span>
                </div>
            </div>
        </x-slot>

        {{-- Placeholder del mapa --}}
        <div class="relative">
            {{-- Contenedor del mapa --}}
            <div
                id="delivery-map"
                class="w-full h-96 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                {{-- Placeholder visual mientras se carga el mapa --}}
                <div class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-map class="w-16 h-16 mb-4 opacity-50" />
                    <p class="text-lg font-medium">Mapa de Entregas en Tiempo Real</p>
                    <p class="text-sm mt-2">Integracion con Google Maps / Mapbox pendiente</p>

                    {{-- Grid de informacion --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 w-full max-w-2xl px-4">
                        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 text-center shadow-sm">
                            <div class="text-2xl font-bold text-primary-600">{{ $stats['total_drivers'] }}</div>
                            <div class="text-xs text-gray-500">Total Repartidores</div>
                        </div>
                        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 text-center shadow-sm">
                            <div class="text-2xl font-bold text-success-600">{{ $stats['drivers_available'] }}</div>
                            <div class="text-xs text-gray-500">Disponibles</div>
                        </div>
                        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 text-center shadow-sm">
                            <div class="text-2xl font-bold text-warning-600">{{ $stats['drivers_on_delivery'] }}</div>
                            <div class="text-xs text-gray-500">En Ruta</div>
                        </div>
                        <div class="bg-white dark:bg-gray-900 rounded-lg p-4 text-center shadow-sm">
                            <div class="text-2xl font-bold text-info-600">{{ $stats['active_deliveries'] }}</div>
                            <div class="text-xs text-gray-500">Entregas Activas</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Datos para JavaScript (para futura integracion) --}}
            <script type="application/json" id="delivery-map-data">
                {
                    "center": @json($this->getMapCenter()),
                    "drivers": @json($this->getActiveDrivers()),
                    "deliveries": @json($this->getActiveDeliveries())
                }
            </script>

            {{-- Script placeholder para futura integracion con Google Maps/Mapbox --}}
            {{--
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const mapData = JSON.parse(document.getElementById('delivery-map-data').textContent);

                    // Inicializar mapa con Google Maps
                    // const map = new google.maps.Map(document.getElementById('delivery-map'), {
                    //     center: { lat: mapData.center.lat, lng: mapData.center.lng },
                    //     zoom: mapData.center.zoom
                    // });

                    // O con Mapbox
                    // mapboxgl.accessToken = 'YOUR_TOKEN';
                    // const map = new mapboxgl.Map({
                    //     container: 'delivery-map',
                    //     style: 'mapbox://styles/mapbox/streets-v11',
                    //     center: [mapData.center.lng, mapData.center.lat],
                    //     zoom: mapData.center.zoom
                    // });

                    // Agregar marcadores de repartidores
                    // mapData.drivers.forEach(driver => {
                    //     // Crear marcador
                    // });

                    // Agregar rutas de entregas
                    // mapData.deliveries.forEach(delivery => {
                    //     // Dibujar ruta
                    // });
                });
            </script>
            @endpush
            --}}
        </div>

        {{-- Lista de repartidores activos --}}
        @php
            $drivers = $this->getActiveDrivers();
        @endphp

        @if($drivers->isNotEmpty())
            <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Repartidores Activos
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($drivers->take(6) as $driver)
                        <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex-shrink-0">
                                @if($driver['avatar'])
                                    <img src="{{ asset('storage/' . $driver['avatar']) }}" alt="{{ $driver['name'] }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <x-heroicon-s-user class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $driver['name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ ucfirst($driver['vehicle']) }} -
                                    <span class="{{ $driver['status'] === 'disponible' ? 'text-success-600' : 'text-warning-600' }}">
                                        {{ $driver['status'] === 'disponible' ? 'Disponible' : 'En entrega' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($drivers->count() > 6)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                        Y {{ $drivers->count() - 6 }} repartidores mas...
                    </p>
                @endif
            </div>
        @else
            <div class="mt-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                <p>No hay repartidores activos en este momento</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
