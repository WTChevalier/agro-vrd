<nav class="bg-white shadow-sm border-b border-gray-100" x-data="{ menuAbierto: false, perfilAbierto: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            {{-- Logo y navegación principal --}}
            <div class="flex">
                {{-- Logo --}}
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('inicio') }}" class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo.svg') }}" alt="SazónRD" class="h-8 w-auto">
                        <span class="text-xl font-bold text-orange-500">SazónRD</span>
                    </a>
                </div>

                {{-- Enlaces de navegación (escritorio) --}}
                <div class="hidden sm:ml-8 sm:flex sm:space-x-6">
                    <a href="{{ route('restaurantes.index') }}"
                       class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('restaurantes.*') ? 'border-orange-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                        Restaurantes
                    </a>
                    <a href="{{ route('restaurantes.cerca-de-mi') }}"
                       class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 text-sm font-medium">
                        Cerca de mí
                    </a>
                    <a href="{{ route('como-funciona') }}"
                       class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 text-sm font-medium">
                        Cómo funciona
                    </a>
                </div>
            </div>

            {{-- Búsqueda --}}
            <div class="flex-1 flex items-center justify-center px-2 lg:ml-6 lg:justify-end">
                <div class="max-w-lg w-full lg:max-w-xs">
                    <form action="{{ route('buscar') }}" method="GET" class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="search" name="q" placeholder="Buscar restaurantes o platos..."
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-full leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                    </form>
                </div>
            </div>

            {{-- Acciones de usuario --}}
            <div class="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-4">
                {{-- Carrito --}}
                <a href="{{ route('carrito.index') }}" class="relative p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    @if(isset($carritoContador) && $carritoContador > 0)
                        <span class="absolute top-0 right-0 bg-orange-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ $carritoContador }}
                        </span>
                    @endif
                </a>

                @auth
                    {{-- Menú de usuario --}}
                    <div class="relative" x-data="{ abierto: false }">
                        <button @click="abierto = !abierto" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <img class="h-8 w-8 rounded-full object-cover"
                                 src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->nombre_completo) . '&background=f97316&color=fff' }}"
                                 alt="{{ auth()->user()->nombre }}">
                            <span class="text-sm font-medium">{{ auth()->user()->nombre }}</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="abierto"
                             @click.away="abierto = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('pedidos.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Mis pedidos
                            </a>
                            <a href="{{ route('perfil.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Mi perfil
                            </a>
                            <a href="{{ route('perfil.direcciones') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Mis direcciones
                            </a>
                            <a href="{{ route('perfil.favoritos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Favoritos
                            </a>
                            <a href="{{ route('perfil.puntos') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Mis puntos
                            </a>
                            <hr class="my-1">
                            <form action="{{ route('sso.cerrar-sesion') }}" method="POST">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('sso.redirigir') }}"
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Iniciar sesión
                    </a>
                @endauth
            </div>

            {{-- Botón menú móvil --}}
            <div class="flex items-center sm:hidden">
                <button @click="menuAbierto = !menuAbierto" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" :class="{ 'hidden': menuAbierto, 'block': !menuAbierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg class="h-6 w-6" :class="{ 'block': menuAbierto, 'hidden': !menuAbierto }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menú móvil --}}
    <div class="sm:hidden" x-show="menuAbierto" x-cloak>
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('restaurantes.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('restaurantes.*') ? 'border-orange-500 text-orange-700 bg-orange-50' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium">
                Restaurantes
            </a>
            <a href="{{ route('restaurantes.cerca-de-mi') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 text-base font-medium">
                Cerca de mí
            </a>
            <a href="{{ route('como-funciona') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 text-base font-medium">
                Cómo funciona
            </a>
        </div>

        @auth
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center px-4">
                    <img class="h-10 w-10 rounded-full" src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->nombre_completo) }}" alt="">
                    <div class="ml-3">
                        <div class="text-base font-medium text-gray-800">{{ auth()->user()->nombre_completo }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ auth()->user()->correo }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <a href="{{ route('pedidos.index') }}" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Mis pedidos</a>
                    <a href="{{ route('perfil.index') }}" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Mi perfil</a>
                    <form action="{{ route('sso.cerrar-sesion') }}" method="POST">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        @else
            <div class="pt-4 pb-3 border-t border-gray-200">
                <a href="{{ route('sso.redirigir') }}" class="block mx-4 py-2 text-center bg-orange-500 text-white rounded-full font-medium hover:bg-orange-600">
                    Iniciar sesión
                </a>
            </div>
        @endauth
    </div>
</nav>
