@extends('layouts.app')

@section('title', 'Checkout - SazónRD')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8" x-data="{ metodoPago: 'efectivo', mostrarNuevaDireccion: false, procesando: false }">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-credit-card mr-2"></i> Finalizar Pedido
    </h1>

    <form action="{{ route('checkout.procesar') }}" method="POST" @submit="procesando = true">
        @csrf
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Dirección -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-map-marker-alt text-sazon-primary mr-2"></i>
                        Dirección de Entrega
                    </h3>

                    @if($direcciones && $direcciones->count() > 0)
                        <div class="space-y-3 mb-4">
                            @foreach($direcciones as $direccion)
                                <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:border-sazon-primary">
                                    <input type="radio" name="direccion_id" value="{{ $direccion->id }}"
                                           {{ $loop->first ? 'checked' : '' }}
                                           class="mt-1 text-sazon-primary">
                                    <div class="ml-3">
                                        <span class="font-medium">{{ $direccion->etiqueta ?? 'Dirección' }}</span>
                                        <p class="text-sm text-gray-600">{{ $direccion->direccion_completa }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <button type="button" @click="mostrarNuevaDireccion = !mostrarNuevaDireccion"
                                class="text-sazon-primary text-sm hover:underline">
                            <i class="fas fa-plus mr-1"></i> Agregar nueva dirección
                        </button>
                    @endif

                    <div x-show="mostrarNuevaDireccion || {{ $direcciones->count() == 0 ? 'true' : 'false' }}"
                         x-cloak class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Completa *</label>
                            <input type="text" name="nueva_direccion[direccion]" placeholder="Calle, número, edificio..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                            <input type="text" name="nueva_direccion[referencia]" placeholder="Cerca de..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                    </div>
                </div>

                <!-- Método de Pago -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-wallet text-sazon-primary mr-2"></i>
                        Método de Pago
                    </h3>

                    <div class="space-y-3">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-sazon-primary"
                               :class="{ 'border-sazon-primary bg-sazon-light': metodoPago == 'efectivo' }">
                            <input type="radio" name="metodo_pago" value="efectivo" x-model="metodoPago"
                                   class="text-sazon-primary">
                            <i class="fas fa-money-bill-wave text-green-600 ml-3 mr-2"></i>
                            <span>Efectivo al recibir</span>
                        </label>

                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-sazon-primary"
                               :class="{ 'border-sazon-primary bg-sazon-light': metodoPago == 'tarjeta' }">
                            <input type="radio" name="metodo_pago" value="tarjeta" x-model="metodoPago"
                                   class="text-sazon-primary">
                            <i class="fas fa-credit-card text-blue-600 ml-3 mr-2"></i>
                            <span>Tarjeta de Crédito/Débito</span>
                        </label>

                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-sazon-primary"
                               :class="{ 'border-sazon-primary bg-sazon-light': metodoPago == 'transferencia' }">
                            <input type="radio" name="metodo_pago" value="transferencia" x-model="metodoPago"
                                   class="text-sazon-primary">
                            <i class="fas fa-university text-purple-600 ml-3 mr-2"></i>
                            <span>Transferencia Bancaria</span>
                        </label>
                    </div>

                    <div x-show="metodoPago == 'efectivo'" x-cloak class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">¿Necesitas cambio?</label>
                        <input type="number" name="cambio_de" placeholder="Ej: 2000"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>

                <!-- Notas -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fas fa-sticky-note text-sazon-primary mr-2"></i>
                        Notas Adicionales
                    </h3>
                    <textarea name="notas" rows="3" placeholder="Instrucciones para el repartidor..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
            </div>

            <!-- Resumen -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-4 sticky top-20">
                    <h3 class="font-semibold text-gray-800 mb-4">Resumen</h3>

                    <div class="max-h-48 overflow-y-auto space-y-2 mb-4">
                        @foreach($items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ $item['cantidad'] }}x {{ $item['nombre'] }}</span>
                                <span>RD$ {{ number_format($item['precio'] * $item['cantidad'], 0) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-3">

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span>RD$ {{ number_format($subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Delivery</span>
                            <span>RD$ {{ number_format($costoDelivery, 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ITBIS (18%)</span>
                            <span>RD$ {{ number_format($itbis, 0) }}</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="flex justify-between font-bold text-lg">
                        <span>Total</span>
                        <span class="text-sazon-primary">RD$ {{ number_format($total, 0) }}</span>
                    </div>

                    <button type="submit" :disabled="procesando"
                            class="mt-4 w-full bg-sazon-primary text-white py-3 rounded-lg font-semibold hover:bg-red-600 transition disabled:opacity-50">
                        <span x-show="!procesando">
                            <i class="fas fa-check mr-2"></i> Confirmar Pedido
                        </span>
                        <span x-show="procesando" x-cloak>
                            <i class="fas fa-spinner fa-spin mr-2"></i> Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection