<!-- Cart Sidebar -->
<div x-data="{ open: false }"
     @toggle-cart.window="open = !open"
     @keydown.escape.window="open = false"
     class="relative z-50">

    <!-- Backdrop -->
    <div x-show="open"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900 bg-opacity-50"
         @click="open = false">
    </div>

    <!-- Sidebar -->
    <div x-show="open"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-xl flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Tu Carrito</h2>
            <button @click="open = false" class="p-2 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Cart Content -->
        <div class="flex-1 overflow-y-auto px-4 py-4" id="cart-content">
            @if(isset($cart) && $cart->items->count() > 0)
                <!-- Restaurant Info -->
                <div class="flex items-center mb-4 pb-4 border-b">
                    <img src="{{ $cart->restaurant->logo_url }}" alt="{{ $cart->restaurant->name }}"
                         class="w-12 h-12 rounded-full object-cover">
                    <div class="ml-3">
                        <h3 class="font-medium text-gray-900">{{ $cart->restaurant->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $cart->items->sum('quantity') }} items</p>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="space-y-4">
                    @foreach($cart->items as $item)
                        <div class="flex items-start" data-item-id="{{ $item->id }}">
                            @if($item->dish?->image)
                                <img src="{{ $item->dish->image_url }}" alt="{{ $item->name }}"
                                     class="w-16 h-16 rounded-lg object-cover">
                            @endif
                            <div class="ml-3 flex-1">
                                <h4 class="font-medium text-gray-900">{{ $item->name }}</h4>
                                @if($item->special_instructions)
                                    <p class="text-xs text-gray-500">{{ $item->special_instructions }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="updateCartItem({{ $item->id }}, {{ $item->quantity - 1 }})"
                                                class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                            -
                                        </button>
                                        <span class="text-gray-900 font-medium">{{ $item->quantity }}</span>
                                        <button onclick="updateCartItem({{ $item->id }}, {{ $item->quantity + 1 }})"
                                                class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                            +
                                        </button>
                                    </div>
                                    <span class="font-medium text-gray-900">
                                        RD$ {{ number_format($item->subtotal, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Coupon -->
                <div class="mt-6 pt-4 border-t">
                    @if($cart->coupon)
                        <div class="flex items-center justify-between bg-green-50 p-3 rounded-lg">
                            <div>
                                <span class="text-green-700 font-medium">{{ $cart->coupon->code }}</span>
                                <span class="text-green-600 text-sm ml-2">-RD$ {{ number_format($cart->discount, 2) }}</span>
                            </div>
                            <button onclick="removeCoupon()" class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @else
                        <form onsubmit="applyCoupon(event)" class="flex space-x-2">
                            <input type="text" name="coupon_code" placeholder="Código de cupón"
                                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-medium transition">
                                Aplicar
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <!-- Empty Cart -->
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <svg class="w-24 h-24 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Tu carrito está vacío</h3>
                    <p class="mt-1 text-gray-500">Agrega platos de tu restaurante favorito</p>
                    <a href="{{ route('restaurants.index') }}"
                       class="mt-4 bg-amber-500 hover:bg-amber-600 text-white px-6 py-2 rounded-full font-medium transition">
                        Ver Restaurantes
                    </a>
                </div>
            @endif
        </div>

        <!-- Footer -->
        @if(isset($cart) && $cart->items->count() > 0)
            <div class="border-t px-4 py-4 bg-gray-50">
                <!-- Totals -->
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="text-gray-900">RD$ {{ number_format($cart->subtotal, 2) }}</span>
                    </div>
                    @if($cart->discount > 0)
                        <div class="flex justify-between text-sm text-green-600">
                            <span>Descuento</span>
                            <span>-RD$ {{ number_format($cart->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-semibold text-lg pt-2 border-t">
                        <span>Total</span>
                        <span>RD$ {{ number_format($cart->subtotal - $cart->discount, 2) }}</span>
                    </div>
                </div>

                <!-- Minimum Order Warning -->
                @if($cart->restaurant->minimum_order > $cart->subtotal)
                    <div class="bg-amber-50 text-amber-700 p-3 rounded-lg text-sm mb-4">
                        Pedido mínimo: RD$ {{ number_format($cart->restaurant->minimum_order, 0) }}.
                        Te faltan RD$ {{ number_format($cart->restaurant->minimum_order - $cart->subtotal, 0) }}.
                    </div>
                @endif

                <!-- Checkout Button -->
                <a href="{{ route('checkout.index') }}"
                   class="block w-full bg-amber-500 hover:bg-amber-600 text-white text-center py-3 rounded-lg font-semibold transition
                          {{ $cart->restaurant->minimum_order > $cart->subtotal ? 'opacity-50 pointer-events-none' : '' }}">
                    Ir al Checkout
                </a>

                <!-- Clear Cart -->
                <button onclick="clearCart()" class="w-full mt-2 text-sm text-gray-500 hover:text-red-500 transition">
                    Vaciar carrito
                </button>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function updateCartItem(itemId, quantity) {
        if (quantity < 1) {
            if (confirm('¿Eliminar este item del carrito?')) {
                removeCartItem(itemId);
            }
            return;
        }

        fetch(`/carrito/actualizar/${itemId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ quantity })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }

    function removeCartItem(itemId) {
        fetch(`/carrito/eliminar/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }

    function clearCart() {
        if (confirm('¿Estás seguro de vaciar el carrito?')) {
            fetch('/carrito/vaciar', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }
    }

    function applyCoupon(event) {
        event.preventDefault();
        const code = event.target.coupon_code.value;

        fetch('/carrito/aplicar-cupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ coupon_code: code })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Cupón inválido');
            }
        });
    }

    function removeCoupon() {
        fetch('/carrito/remover-cupon', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
</script>
@endpush
