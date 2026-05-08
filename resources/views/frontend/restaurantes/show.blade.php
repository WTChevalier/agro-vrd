@extends('layouts.app')

@section('title', $restaurante->nombre . ' - SazónRD')

@section('content')
<div x-data="menuRestaurante()" class="bg-gray-50 min-h-screen">
    <!-- Header del restaurante -->
    <div class="relative h-64 md:h-80">
        <img src="{{ $restaurante->imagen_portada ?? 'https://via.placeholder.com/1200x400?text=Restaurante' }}"
             alt="{{ $restaurante->nombre }}"
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>

        <div class="absolute bottom-0 left-0 right-0 p-6 text-white">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-end justify-between">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $restaurante->nombre }}</h1>
                        <p class="text-white/80 mb-2">
                            {{ $restaurante->categorias->pluck('nombre')->implode(' • ') }}
                        </p>
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <span class="flex items-center">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                {{ number_format($restaurante->calificacion_promedio, 1) }}
                            </span>
                            <span class="flex items-center">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $restaurante->tiempo_entrega_estimado }} min
                            </span>
                            <span class="flex items-center">
                                <i class="fas fa-motorcycle mr-1"></i>
                                @if($restaurante->costo_delivery == 0)
                                    Delivery Gratis
                                @else
                                    RD$ {{ number_format($restaurante->costo_delivery, 0) }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        @if($restaurante->abierto)
                            <span class="bg-green-500 text-white px-4 py-2 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i> Abierto
                            </span>
                        @else
                            <span class="bg-red-500 text-white px-4 py-2 rounded-full">
                                <i class="fas fa-times-circle mr-1"></i> Cerrado
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Menú -->
            <div class="lg:col-span-2">
                <!-- Categorías del menú -->
                <div class="bg-white rounded-lg shadow-md mb-6 sticky top-16 z-40">
                    <div class="flex overflow-x-auto py-3 px-4 gap-2">
                        @foreach($categorias as $categoria)
                            <a href="#categoria-{{ $categoria->id }}"
                               class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium transition
                                      hover:bg-sazon-primary hover:text-white bg-gray-100 text-gray-700">
                                {{ $categoria->nombre }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Productos por categoría -->
                @foreach($categorias as $categoria)
                    <div id="categoria-{{ $categoria->id }}" class="mb-8">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">{{ $categoria->nombre }}</h2>

                        <div class="space-y-4">
                            @foreach($categoria->productos->where('activo', true) as $producto)
                                <div class="bg-white rounded-lg shadow-md p-4 flex gap-4 hover:shadow-lg transition cursor-pointer"
                                     @click="abrirModal({{ $producto->id }})">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800">{{ $producto->nombre }}</h3>
                                        @if($producto->es_popular)
                                            <span class="text-xs text-sazon-accent">
                                                <i class="fas fa-fire mr-1"></i> Popular
                                            </span>
                                        @endif
                                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $producto->descripcion }}</p>
                                        <div class="mt-2 flex items-center gap-2">
                                            @if($producto->precio_oferta)
                                                <span class="text-lg font-bold text-sazon-primary">
                                                    RD$ {{ number_format($producto->precio_oferta, 0) }}
                                                </span>
                                                <span class="text-sm text-gray-400 line-through">
                                                    RD$ {{ number_format($producto->precio, 0) }}
                                                </span>
                                            @else
                                                <span class="text-lg font-bold text-sazon-primary">
                                                    RD$ {{ number_format($producto->precio, 0) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="w-24 h-24 rounded-lg overflow-hidden flex-shrink-0">
                                        <img src="{{ $producto->imagen ?? 'https://via.placeholder.com/100?text=Plato' }}"
                                             alt="{{ $producto->nombre }}"
                                             class="w-full h-full object-cover">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Info del restaurante -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Información</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt text-sazon-primary mt-1 mr-3 w-4"></i>
                            <span class="text-gray-600">{{ $restaurante->direccion }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-sazon-primary mr-3 w-4"></i>
                            <span class="text-gray-600">{{ $restaurante->horario_apertura }} - {{ $restaurante->horario_cierre }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-shopping-bag text-sazon-primary mr-3 w-4"></i>
                            <span class="text-gray-600">Pedido mínimo: RD$ {{ number_format($restaurante->pedido_minimo, 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Mini Carrito -->
                <div class="bg-white rounded-lg shadow-md p-4 sticky top-32">
                    <h3 class="font-semibold text-gray-800 mb-3">
                        <i class="fas fa-shopping-cart mr-2"></i> Tu Pedido
                    </h3>

                    @if(session('carrito') && count(session('carrito')) > 0)
                        @php
                            $subtotal = 0;
                            $carritoRestaurante = collect(session('carrito'))->where('restaurante_id', $restaurante->id);
                        @endphp

                        @if($carritoRestaurante->count() > 0)
                            <div class="space-y-3 max-h-64 overflow-y-auto">
                                @foreach($carritoRestaurante as $key => $item)
                                    @php $subtotal += $item['precio'] * $item['cantidad']; @endphp
                                    <div class="flex justify-between items-center text-sm">
                                        <div class="flex-1">
                                            <span class="font-medium">{{ $item['cantidad'] }}x</span>
                                            {{ $item['nombre'] }}
                                        </div>
                                        <span class="text-gray-600">RD$ {{ number_format($item['precio'] * $item['cantidad'], 0) }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <hr class="my-3">
                            <div class="flex justify-between font-semibold">
                                <span>Subtotal</span>
                                <span>RD$ {{ number_format($subtotal, 0) }}</span>
                            </div>
                            <a href="{{ route('carrito.index') }}"
                               class="mt-4 w-full bg-sazon-primary text-white py-3 rounded-lg text-center block hover:bg-red-600 transition">
                                Ver Carrito
                            </a>
                        @else
                            <p class="text-gray-500 text-center py-4">Tu carrito está vacío</p>
                        @endif
                    @else
                        <p class="text-gray-500 text-center py-4">Tu carrito está vacío</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar al Carrito -->
    <div x-show="modalAbierto" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="flex items-end sm:items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/50" @click="cerrarModal()"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
                 x-show="modalAbierto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                <template x-if="productoSeleccionado">
                    <div>
                        <div class="h-48 overflow-hidden">
                            <img :src="productoSeleccionado.imagen || 'https://via.placeholder.com/400x200?text=Plato'"
                                 :alt="productoSeleccionado.nombre"
                                 class="w-full h-full object-cover">
                        </div>

                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800" x-text="productoSeleccionado.nombre"></h3>
                            <p class="text-gray-500 mt-2" x-text="productoSeleccionado.descripcion"></p>

                            <div class="mt-4">
                                <span class="text-2xl font-bold text-sazon-primary">
                                    RD$ <span x-text="formatNumber(productoSeleccionado.precio_oferta || productoSeleccionado.precio)"></span>
                                </span>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notas especiales</label>
                                <textarea x-model="notas" rows="2"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary"
                                          placeholder="Ej: Sin cebolla..."></textarea>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="font-medium">Cantidad</span>
                                <div class="flex items-center border rounded-lg">
                                    <button type="button" @click="cantidad > 1 ? cantidad-- : null"
                                            class="px-4 py-2 text-sazon-primary hover:bg-gray-100">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="px-4 py-2 font-semibold" x-text="cantidad"></span>
                                    <button type="button" @click="cantidad++"
                                            class="px-4 py-2 text-sazon-primary hover:bg-gray-100">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="button" @click="agregarAlCarrito()"
                                    class="mt-6 w-full bg-sazon-primary text-white py-3 rounded-lg font-semibold hover:bg-red-600 transition flex items-center justify-center">
                                <span>Agregar al carrito - RD$ </span>
                                <span x-text="formatNumber((productoSeleccionado.precio_oferta || productoSeleccionado.precio) * cantidad)"></span>
                            </button>
                        </div>

                        <button type="button" @click="cerrarModal()"
                                class="absolute top-4 right-4 bg-white rounded-full p-2 shadow-md hover:bg-gray-100">
                            <i class="fas fa-times text-gray-600"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const productos = @json($restaurante->productos->keyBy('id'));
    const restauranteId = {{ $restaurante->id }};

    function menuRestaurante() {
        return {
            modalAbierto: false,
            productoSeleccionado: null,
            cantidad: 1,
            notas: '',

            abrirModal(productoId) {
                this.productoSeleccionado = productos[productoId];
                this.cantidad = 1;
                this.notas = '';
                this.modalAbierto = true;
            },

            cerrarModal() {
                this.modalAbierto = false;
                this.productoSeleccionado = null;
            },

            formatNumber(num) {
                return new Intl.NumberFormat('es-DO').format(num);
            },

            async agregarAlCarrito() {
                try {
                    const response = await fetch('{{ route("carrito.agregar") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            producto_id: this.productoSeleccionado.id,
                            cantidad: this.cantidad,
                            notas: this.notas
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al agregar');
                    }
                } catch (error) {
                    alert('Error al agregar al carrito');
                }
            }
        }
    }
</script>
@endpush
@endsection