<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="{{ asset('images/logo-sazonrd.svg') }}" alt="SazónRD" class="h-10 w-auto">
                    <span class="ml-2 text-xl font-bold text-amber-600 hidden sm:block">SazónRD</span>
                </a>
            </div>

            <!-- Search Bar (Desktop) -->
            <div class="hidden md:flex items-center flex-1 max-w-lg mx-8">
                <form action="{{ route('search') }}" method="GET" class="w-full">
                    <div class="relative">
                        <input type="text" name="q" placeholder="Buscar restaurantes o platos..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-full focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                               value="{{ request('q') }}">
                        <div class="absolute left-3 top-2.5">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="{{ route('restaurants.index') }}" class="text-gray-600 hover:text-amber-600 px-3 py-2 text-sm font-medium">
                    Restaurantes
                </a>
                <a href="{{ route('restaurants.nearby') }}" class="text-gray-600 hover:text-amber-600 px-3 py-2 text-sm font-medium">
                    Cerca de mí
                </a>

                @auth
                    <a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-amber-600 px-3 py-2 text-sm font-medium">
                        Mis Pedidos
                    </a>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center text-gray-600 hover:text-amber-600">
                            <img src="{{ auth()->user()->avatar ?? asset('images/avatar-default.png') }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="h-8 w-8 rounded-full object-cover">
                            <span class="ml-2 text-sm font-medium hidden lg:block">{{ auth()->user()->name }}</span>
                            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" @click.away="open = false"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Mi Perfil
                            </a>
                            <a href="{{ route('profile.addresses') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Mis Direcciones
                            </a>
                            <a href="{{ route('profile.favorites') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Favoritos
                            </a>
                            <a href="{{ route('profile.wallet') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Wallet (RD$ {{ number_format(auth()->user()->wallet_balance, 2) }})
                            </a>
                            <a href="{{ route('profile.loyalty') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Puntos ({{ number_format(auth()->user()->loyalty_points) }})
                            </a>
                            <hr class="my-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-amber-600 px-3 py-2 text-sm font-medium">
                        Iniciar Sesión
                    </a>
                    <a href="{{ route('register') }}" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full text-sm font-medium">
                        Registrarse
                    </a>
                @endauth

                <!-- Cart Button -->
                <button @click="$dispatch('toggle-cart')" class="relative p-2 text-gray-600 hover:text-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    @if(session('cart_count', 0) > 0)
                        <span class="absolute -top-1 -right-1 bg-amber-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ session('cart_count') }}
                        </span>
                    @endif
                </button>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button @click="$dispatch('toggle-cart')" class="relative p-2 mr-2 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    @if(session('cart_count', 0) > 0)
                        <span class="absolute -top-1 -right-1 bg-amber-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ session('cart_count') }}
                        </span>
                    @endif
                </button>

                <button type="button" x-data @click="$dispatch('toggle-mobile-menu')" class="p-2 text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-data="{ open: false }" @toggle-mobile-menu.window="open = !open" x-show="open" class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t">
            <!-- Search (Mobile) -->
            <form action="{{ route('search') }}" method="GET" class="px-2 mb-3">
                <input type="text" name="q" placeholder="Buscar..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-full text-sm"
                       value="{{ request('q') }}">
            </form>

            <a href="{{ route('restaurants.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                Restaurantes
            </a>
            <a href="{{ route('restaurants.nearby') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                Cerca de mí
            </a>

            @auth
                <a href="{{ route('orders.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                    Mis Pedidos
                </a>
                <a href="{{ route('profile.index') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                    Mi Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-3 py-2 text-base font-medium text-red-600 hover:bg-gray-100 rounded-md">
                        Cerrar Sesión
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                    Iniciar Sesión
                </a>
                <a href="{{ route('register') }}" class="block px-3 py-2 text-base font-medium text-amber-600 hover:bg-gray-100 rounded-md">
                    Registrarse
                </a>
            @endauth
        </div>
    </div>
</nav>
