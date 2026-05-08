@extends('layouts.app')

@section('title', 'Pedido Confirmado - SazónRD')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12 text-center">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check text-5xl text-green-600"></i>
        </div>

        <h1 class="text-3xl font-bold text-gray-800 mb-2">¡Pedido Confirmado!</h1>
        <p class="text-gray-600 mb-6">Tu pedido está siendo preparado.</p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-gray-500">Número de pedido</p>
            <p class="text-2xl font-bold text-sazon-primary">{{ $pedido->codigo }}</p>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="text-center">
                <div class="w-12 h-12 bg-sazon-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-clock text-sazon-primary"></i>
                </div>
                <p class="text-xs text-gray-500">Tiempo</p>
                <p class="font-semibold">30-45 min</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-sazon-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-store text-sazon-primary"></i>
                </div>
                <p class="text-xs text-gray-500">Restaurante</p>
                <p class="font-semibold">{{ $pedido->restaurante->nombre }}</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-sazon-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                    <i class="fas fa-money-bill text-sazon-primary"></i>
                </div>
                <p class="text-xs text-gray-500">Total</p>
                <p class="font-semibold">RD$ {{ number_format($pedido->total, 0) }}</p>
            </div>
        </div>

        <div class="mt-8 space-y-3">
            <a href="{{ route('pedidos.seguimiento', $pedido->codigo) }}"
               class="block w-full bg-sazon-primary text-white py-3 rounded-lg font-semibold hover:bg-red-600 transition">
                <i class="fas fa-map-marker-alt mr-2"></i> Seguir mi pedido
            </a>
            <a href="{{ route('home') }}"
               class="block w-full border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50 transition">
                Volver al inicio
            </a>
        </div>
    </div>
</div>
@endsection