{{-- Sidebar del Carrito de Compras --}}
<div x-data="{ abierto: false }"
     x-on:abrir-carrito.window="abierto = true"
     x-on:keydown.escape.window="abierto = false"
     class="relative z-50"
     aria-labelledby="slide-over-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="abierto"
         x-transition:enter="ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
         @click="abierto = false">
    </div>

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                {{-- Panel del carrito --}}
                <div x-show="abierto"
                     x-transition:enter="transform transition ease-in-out duration-300"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="pointer-events-auto w-screen max-w-md">
                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">

                        {{-- Encabezado --}}
                        <div class="flex items-start justify-between px-4 py-4 border-b">
                            <h2 class="text-lg font-semibold text-gray-900" id="slide-over-title">
                                Tu carrito
                            </h2>
                            <button type="button"
                                    @click="abierto = false"
                                    class="ml-3 flex h-8 w-8 items-center justify-center rounded-full hover:bg-gray-100">
                                <span class="sr-only">Cerrar panel</span>
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Contenido del carrito (se carga dinámicamente) --}}
                        <div class="flex-1 overflow-y-auto px-4 py-4" id="carrito-contenido">
                            {{-- Este contenido se actualiza vía AJAX/Livewire --}}
                            <div class="flex flex-col items-center justify-center h-full text-gray-500">
                                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-lg font-medium">Tu carrito está vacío</p>
                                <p class="text-sm mt-1">Agrega productos de tu restaurante favorito</p>
                            </div>
                        </div>

                        {{-- Footer con totales y botón de pago --}}
                        <div class="border-t border-gray-200 px-4 py-4" id="carrito-footer" style="display: none;">
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium" id="carrito-subtotal">RD$0.00</span>
                                </div>
                                <div class="flex justify-between text-sm" id="carrito-descuento-row" style="display: none;">
                                    <span class="text-green-600">Descuento</span>
                                    <span class="font-medium text-green-600" id="carrito-descuento">-RD$0.00</span>
                                </div>
                            </div>
                            <div class="flex justify-between text-lg font-semibold mb-4">
                                <span>Total</span>
                                <span id="carrito-total">RD$0.00</span>
                            </div>
                            <a href="{{ route('pago.index') }}"
                               class="block w-full bg-orange-500 hover:bg-orange-600 text-white text-center font-semibold py-3 rounded-full transition-colors">
                                Continuar al pago
                            </a>
                            <a href="{{ route('carrito.index') }}"
                               class="block w-full text-center text-orange-500 hover:text-orange-600 font-medium py-2 mt-2">
                                Ver carrito completo
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Función para abrir el carrito
    window.abrirCarrito = function() {
        window.dispatchEvent(new CustomEvent('abrir-carrito'));
    }

    // Función para actualizar el contenido del carrito
    window.actualizarCarritoSidebar = function() {
        fetch('/api/v1/carrito')
            .then(response => response.json())
            .then(data => {
                if (data.exito && data.datos.items && data.datos.items.length > 0) {
                    // Actualizar contenido
                    // ... (implementar renderizado de items)
                    document.getElementById('carrito-footer').style.display = 'block';
                    document.getElementById('carrito-subtotal').textContent = 'RD$' + data.datos.subtotal.toLocaleString();
                    document.getElementById('carrito-total').textContent = 'RD$' + data.datos.total.toLocaleString();
                }
            })
            .catch(error => console.error('Error cargando carrito:', error));
    }
</script>
@endpush
