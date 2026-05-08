<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Panel Restaurante</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen" x-data="{ sidebarOpen: true }">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-gray-900 text-white transition-all duration-300">
            <div class="p-4 border-b border-gray-700">
                <div class="flex items-center justify-between">
                    <span x-show="sidebarOpen" class="text-xl font-bold">
                        <span class="text-red-500">Sazón</span>RD
                    </span>
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>

            <nav class="p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('restaurante.dashboard') }}"
                           class="flex items-center p-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('restaurante.dashboard') ? 'bg-gray-800' : '' }}">
                            <i class="fas fa-tachometer-alt w-6"></i>
                            <span x-show="sidebarOpen" class="ml-3">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('restaurante.pedidos.index') }}"
                           class="flex items-center p-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('restaurante.pedidos.*') ? 'bg-gray-800' : '' }}">
                            <i class="fas fa-receipt w-6"></i>
                            <span x-show="sidebarOpen" class="ml-3">Pedidos</span>
                            @php
                                $pendientes = \App\Models\Pedido::where('restaurante_id', auth()->user()->restaurante->id ?? 0)
                                    ->whereIn('estado', ['pendiente', 'confirmado'])->count();
                            @endphp
                            @if($pendientes > 0)
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendientes }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('restaurante.menu.index') }}"
                           class="flex items-center p-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('restaurante.menu.*') ? 'bg-gray-800' : '' }}">
                            <i class="fas fa-utensils w-6"></i>
                            <span x-show="sidebarOpen" class="ml-3">Menú</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('restaurante.configuracion') }}"
                           class="flex items-center p-3 rounded-lg hover:bg-gray-800 {{ request()->routeIs('restaurante.configuracion') ? 'bg-gray-800' : '' }}">
                            <i class="fas fa-cog w-6"></i>
                            <span x-show="sidebarOpen" class="ml-3">Configuración</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center p-3 rounded-lg hover:bg-gray-800 w-full">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span x-show="sidebarOpen" class="ml-3">Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-800">@yield('title')</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Toggle Abierto/Cerrado -->
                        <div x-data="{ abierto: {{ auth()->user()->restaurante->abierto ?? 'false' }} }" class="flex items-center">
                            <span class="text-sm mr-2" :class="abierto ? 'text-green-600' : 'text-red-600'">
                                <span x-text="abierto ? 'Abierto' : 'Cerrado'"></span>
                            </span>
                            <button @click="toggleEstado()" class="relative">
                                <div :class="abierto ? 'bg-green-500' : 'bg-gray-300'" class="w-12 h-6 rounded-full transition"></div>
                                <div :class="abierto ? 'translate-x-6' : 'translate-x-0'" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition transform"></div>
                            </button>
                        </div>
                        <span class="text-gray-600">{{ auth()->user()->restaurante->nombre ?? 'Mi Restaurante' }}</span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        async function toggleEstado() {
            const response = await fetch('{{ route("restaurante.toggle-estado") }}', {
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
    </script>
    @stack('scripts')
</body>
</html>