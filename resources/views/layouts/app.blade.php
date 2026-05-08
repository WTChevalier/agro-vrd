<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Sprint i18n turismo - hreflang multilingüe (2026-04-30) --}}
    @php
        $currentPath = parse_url(url()->current(), PHP_URL_PATH) ?? '/';
        $baseUrl = rtrim(config('app.url'), '/');
        $sep = (str_contains($currentPath, '?') ? '&' : '?');
        $i18nLocales = ['en' => 'en', 'fr' => 'fr', 'it' => 'it', 'de' => 'de', 'pt' => 'pt-BR', 'ru' => 'ru', 'ja' => 'ja', 'ko' => 'ko', 'zh' => 'zh-CN'];
        $esUrl = $baseUrl . $currentPath;
    @endphp
    <link rel="alternate" hreflang="es-DO" href="{{ $esUrl }}">
    <link rel="alternate" hreflang="es" href="{{ $esUrl }}">
    @foreach($i18nLocales as $code => $hreflang)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $esUrl . $sep . 'lang=' . $code }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $esUrl }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SazónRD - Delivery de Comida Dominicana')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'sazon': {
                            'primary': '#E63946',
                            'secondary': '#1D3557',
                            'accent': '#F4A261',
                            'light': '#F1FAEE',
                            'dark': '#457B9D'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { font-family: 'Poppins', sans-serif; }
        .gradient-sazon { background: linear-gradient(135deg, #E63946 0%, #F4A261 100%); }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50" x-data="{ mobileMenu: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <span class="text-2xl font-bold text-sazon-primary">Sazón</span>
                        <span class="text-2xl font-bold text-sazon-secondary">RD</span>
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden md:flex items-center flex-1 max-w-lg mx-8">
                    <form action="{{ route('buscar') }}" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="q" placeholder="Buscar restaurantes o platos..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-sazon-primary focus:border-transparent"
                                   value="{{ request('q') }}">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </form>
                </div>

                <!-- Nav Links -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('restaurantes.index') }}" class="text-gray-600 hover:text-sazon-primary transition">
                        <i class="fas fa-utensils mr-1"></i> Restaurantes
                    </a>

                    <!-- Carrito -->
                    <a href="{{ route('carrito.index') }}" class="relative text-gray-600 hover:text-sazon-primary transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        @if(session('carrito') && count(session('carrito')) > 0)
                            <span class="absolute -top-2 -right-2 bg-sazon-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                {{ count(session('carrito')) }}
                            </span>
                        @endif
                    </a>

                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-gray-600 hover:text-sazon-primary">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=E63946&color=fff"
                                     class="w-8 h-8 rounded-full mr-2">
                                <span>{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="{{ route('pedidos.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-receipt mr-2"></i> Panel Admin</a>
                                <a href="{{ url('/admin') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cogs mr-2"></i> Panel Admin
                                </a>
                                <a href="{{ url('/pedidos') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-list mr-2"></i> Mis Pedidos
                                </a>
                                <a href="{{ url('/perfil') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> Mi Perfil
                                </a>
                                <hr class="my-1">
                                <form action="{{ url('/logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ url('/admin/login') }}" class="text-gray-600 hover:text-sazon-primary transition">
                            Iniciar Sesión
                        </a>
                        <a href="{{ url('/admin/login') }}" class="bg-sazon-primary text-white px-4 py-2 rounded-full hover:bg-red-600 transition">
                            Registrarse
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <a href="{{ route('carrito.index') }}" class="relative mr-4 text-gray-600">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        @if(session('carrito') && count(session('carrito')) > 0)
                            <span class="absolute -top-2 -right-2 bg-sazon-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                {{ count(session('carrito')) }}
                            </span>
                        @endif
                    </a>
                    <button @click="mobileMenu = !mobileMenu" class="text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenu" @click.away="mobileMenu = false" x-cloak class="md:hidden bg-white border-t">
            <div class="px-4 py-3">
                <form action="{{ route('buscar') }}" method="GET">
                    <input type="text" name="q" placeholder="Buscar..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-sazon-primary">
                </form>
            </div>
            <a href="{{ route('restaurantes.index') }}" class="block px-4 py-3 text-gray-600 hover:bg-gray-100">
                <i class="fas fa-utensils mr-2"></i> Restaurantes
            </a>
            @auth
                <a href="{{ route('pedidos.index') }}" class="block px-4 py-3 text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-receipt mr-2"></i> Panel Admin</a>
                                <a href="{{ url('/admin') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cogs mr-2"></i> Panel Admin
                                </a>
                                <a href="{{ url('/pedidos') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-list mr-2"></i> Mis Pedidos
                </a>
                <a href="{{ url('/perfil') }}" class="block px-4 py-3 text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i> Mi Perfil
                </a>
                <form action="{{ url('/logout') }}" method="POST" class="border-t">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-3 text-gray-600 hover:bg-gray-100">
                        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                    </button>
                </form>
            @else
                <a href="{{ url('/admin/login') }}" class="block px-4 py-3 text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
                </a>
                <a href="{{ url('/admin/login') }}" class="block px-4 py-3 text-sazon-primary hover:bg-gray-100">
                    <i class="fas fa-user-plus mr-2"></i> Registrarse
                </a>
            @endauth
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-sazon-secondary text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl font-bold text-sazon-primary">Sazón</span>
                        <span class="text-2xl font-bold text-white">RD</span>
                    </div>
                    <p class="text-gray-300 text-sm">
                        La mejor plataforma de delivery de comida dominicana.
                    </p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Descubre</h4>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><a href="{{ route('restaurantes.index') }}" class="hover:text-white">Restaurantes</a></li>
                        <li><a href="#" class="hover:text-white">Ofertas del Día</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Información</h4>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><a href="#" class="hover:text-white">Sobre Nosotros</a></li>
                        <li><a href="#" class="hover:text-white">Términos y Condiciones</a></li>
                        <li><a href="#" class="hover:text-white">Política de Privacidad</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Contacto</h4>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><i class="fas fa-envelope mr-2"></i> info@sazonrd.com</li>
                        <li><i class="fas fa-phone mr-2"></i> +1 (809) 555-0123</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-600 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; {{ date('Y') }} SazónRD. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>