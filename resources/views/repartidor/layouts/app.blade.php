<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Repartidor SazónRD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-green-600 text-white px-4 py-3 sticky top-0 z-50">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-xl font-bold">
                    <span class="text-yellow-300">Sazón</span>RD
                </span>
                <span class="ml-2 text-sm bg-white/20 px-2 py-0.5 rounded">Repartidor</span>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Toggle Disponible -->
                <div x-data="{ disponible: {{ auth()->user()->repartidor->disponible ?? 'false' }} }">
                    <button @click="toggleDisponibilidad()"
                            :class="disponible ? 'bg-green-400' : 'bg-gray-400'"
                            class="px-3 py-1 rounded-full text-sm font-medium">
                        <span x-text="disponible ? 'En línea' : 'Desconectado'"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="pb-20">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4 rounded">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg">
        <div class="flex justify-around py-2">
            <a href="{{ route('repartidor.dashboard') }}"
               class="flex flex-col items-center py-2 px-4 {{ request()->routeIs('repartidor.dashboard') ? 'text-green-600' : 'text-gray-500' }}">
                <i class="fas fa-home text-xl"></i>
                <span class="text-xs mt-1">Inicio</span>
            </a>
            <a href="{{ route('repartidor.pedido.activo') }}"
               class="flex flex-col items-center py-2 px-4 {{ request()->routeIs('repartidor.pedido.activo') ? 'text-green-600' : 'text-gray-500' }}">
                <i class="fas fa-motorcycle text-xl"></i>
                <span class="text-xs mt-1">Activo</span>
            </a>
            <a href="{{ route('repartidor.historial') }}"
               class="flex flex-col items-center py-2 px-4 {{ request()->routeIs('repartidor.historial') ? 'text-green-600' : 'text-gray-500' }}">
                <i class="fas fa-history text-xl"></i>
                <span class="text-xs mt-1">Historial</span>
            </a>
            <a href="{{ route('repartidor.perfil') }}"
               class="flex flex-col items-center py-2 px-4 {{ request()->routeIs('repartidor.perfil') ? 'text-green-600' : 'text-gray-500' }}">
                <i class="fas fa-user text-xl"></i>
                <span class="text-xs mt-1">Perfil</span>
            </a>
        </div>
    </nav>

    <script>
        async function toggleDisponibilidad() {
            const response = await fetch('{{ route("repartidor.toggle-disponibilidad") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const data = await response.json();
            if (data.success) {
                window.location.reload();
            }
        }

        // Enviar ubicación cada 30 segundos
        if (navigator.geolocation) {
            setInterval(() => {
                navigator.geolocation.getCurrentPosition(position => {
                    fetch('{{ route("repartidor.actualizar-ubicacion") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            latitud: position.coords.latitude,
                            longitud: position.coords.longitude
                        })
                    });
                });
            }, 30000);
        }
    </script>
    @stack('scripts')
</body>
</html>