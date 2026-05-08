@extends('layouts.app')

@section('title', 'Mi Carrito - SazónRD')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-shopping-cart mr-2"></i> Mi Carrito
    </h1>

    @if($items->count() > 0)
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                @foreach($items as $key => $item)
                    <div class="bg-white rounded-lg shadow-md p-4">
                        <div class="flex gap-4">
                            <img src="{{ $item['imagen'] ?? 'https://via.placeholder.com/80?text=Plato' }}"
                                 alt="{{ $item['nombre'] }}"
                                 class="w-20 h-20 rounded-lg object-cover">

                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-800">{{ $item['nombre'] }}</h4>
                                @if($item['notas'])
                                    <p class="text-sm text-gray-500">{{ $item['notas'] }}</p>
                                @endif
                                <p class="text-sazon-primary font-bold mt-1">
                                    RD$ {{ number_format($item['precio'], 0) }}
                                </p>
                            </div>

                            <div class="flex flex-col items-end justify-between">
                                <form action="{{ route('carrito.eliminar', $key) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                                <div class="flex items-center border rounded-lg">
                                    <form action="{{ route('carrito.actualizar', $key) }}" method="POST" class="flex items-center">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" name="cantidad" value="{{ $item['cantidad'] - 1 }}"
                                                class="px-3 py-1 text-sazon-primary hover:bg-gray-100">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="px-3 py-1 font-semibold">{{ $item['cantidad'] }}</span>
                                        <button type="submit" name="cantidad" value="{{ $item['cantidad'] + 1 }}"
                                                class="px-3 py-1 text-sazon-primary hover:bg-gray-100">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <form action="{{ route('carrito.vaciar') }}" method="POST" class="text-center">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm"
                            onclick="return confirm('¿Vaciar el carrito?')">
                        <i class="fas fa-trash-alt mr-1"></i> Vaciar carrito
                    </button>
                </form>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-4 sticky top-20">
                    <h3 class="font-semibold text-gray-800 mb-4">Resumen</h3>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span>RD$ {{ number_format($subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Delivery</span>
                            <span>
                                @if($costoDelivery == 0)
                                    <span class="text-green-600">Gratis</span>
                                @else
                                    RD$ {{ number_format($costoDelivery, 0) }}
                                @endif
                            </span>
                        </div>
                        @if($descuento > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Descuento</span>
                                <span>-RD$ {{ number_format($descuento, 0) }}</span>
                            </div>
                        @endif
                    </div>

                    <hr class="my-4">

                    <div class="flex justify-between font-bold text-lg">
                        <span>Total</span>
                        <span class="text-sazon-primary">RD$ {{ number_format($total, 0) }}</span>
                    </div>

                    <div class="mt-4">
                        <form action="{{ route('carrito.index') }}" method="GET" class="flex gap-2">
                            <input type="text" name="cupon" placeholder="Código de cupón"
                                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                   value="{{ request('cupon') }}">
                            <button type="submit" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">
                                Aplicar
                            </button>
                        </form>
                    </div>

                    <a href="{{ route('checkout.index') }}"
                       class="mt-4 w-full bg-sazon-primary text-white py-3 rounded-lg text-center block hover:bg-red-600 transition font-semibold">
                        Proceder al Pago <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-shopping-basket text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Tu carrito está vacío</h3>
            <p class="text-gray-500 mb-4">¡Explora nuestros restaurantes!</p>
            <a href="{{ route('restaurantes.index') }}"
               class="inline-block bg-sazon-primary text-white px-6 py-3 rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-utensils mr-2"></i> Ver Restaurantes
            </a>
        </div>
    @endif
</div>
@endsection