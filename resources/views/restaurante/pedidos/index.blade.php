@extends('restaurante.layouts.app')

@section('title', 'Pedidos')

@section('content')
<!-- Filtros -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <div class="flex flex-wrap gap-4 items-center">
        <a href="{{ route('restaurante.pedidos.index') }}"
           class="px-4 py-2 rounded-lg {{ !request('estado') ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
            Todos
        </a>
        <a href="{{ route('restaurante.pedidos.index', ['estado' => 'pendiente']) }}"
           class="px-4 py-2 rounded-lg {{ request('estado') == 'pendiente' ? 'bg-yellow-500 text-white' : 'bg-gray-100' }}">
            Pendientes ({{ $contadores['pendientes'] }})
        </a>
        <a href="{{ route('restaurante.pedidos.index', ['estado' => 'confirmado']) }}"
           class="px-4 py-2 rounded-lg {{ request('estado') == 'confirmado' ? 'bg-blue-500 text-white' : 'bg-gray-100' }}">
            Confirmados ({{ $contadores['confirmados'] }})
        </a>
        <a href="{{ route('restaurante.pedidos.index', ['estado' => 'preparando']) }}"
           class="px-4 py-2 rounded-lg {{ request('estado') == 'preparando' ? 'bg-orange-500 text-white' : 'bg-gray-100' }}">
            Preparando ({{ $contadores['preparando'] }})
        </a>
    </div>
</div>

<!-- Lista de Pedidos -->
<div class="space-y-4">
    @forelse($pedidos as $pedido)
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-lg">{{ $pedido->codigo }}</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            @switch($pedido->estado)
                                @case('pendiente') bg-yellow-100 text-yellow-800 @break
                                @case('confirmado') bg-blue-100 text-blue-800 @break
                                @case('preparando') bg-orange-100 text-orange-800 @break
                                @case('listo') bg-green-100 text-green-800 @break
                                @case('en_camino') bg-purple-100 text-purple-800 @break
                                @default bg-gray-100 text-gray-800
                            @endswitch">
                            {{ ucfirst(str_replace('_', ' ', $pedido->estado)) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $pedido->created_at->format('d/m/Y H:i') }} •
                        {{ $pedido->usuario->name ?? 'Cliente' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-green-600">RD$ {{ number_format($pedido->total, 0) }}</p>
                    <p class="text-sm text-gray-500">{{ $pedido->metodo_pago }}</p>
                </div>
            </div>

            <!-- Productos -->
            <div class="mt-4 bg-gray-50 rounded-lg p-3">
                @foreach($pedido->detalles as $detalle)
                    <div class="flex justify-between text-sm py-1">
                        <span>{{ $detalle->cantidad }}x {{ $detalle->nombre_producto }}</span>
                        <span>RD$ {{ number_format($detalle->subtotal, 0) }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Dirección -->
            <div class="mt-3 text-sm text-gray-600">
                <i class="fas fa-map-marker-alt mr-1"></i>
                {{ $pedido->direccion_entrega }}
            </div>

            <!-- Acciones -->
            <div class="mt-4 flex gap-2">
                @if($pedido->estado === 'pendiente')
                    <button onclick="cambiarEstado({{ $pedido->id }}, 'confirmado')"
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        <i class="fas fa-check mr-1"></i> Confirmar
                    </button>
                    <button onclick="rechazarPedido({{ $pedido->id }})"
                            class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">
                        <i class="fas fa-times mr-1"></i> Rechazar
                    </button>
                @elseif($pedido->estado === 'confirmado')
                    <button onclick="cambiarEstado({{ $pedido->id }}, 'preparando')"
                            class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600">
                        <i class="fas fa-utensils mr-1"></i> Iniciar Preparación
                    </button>
                @elseif($pedido->estado === 'preparando')
                    <button onclick="cambiarEstado({{ $pedido->id }}, 'listo')"
                            class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                        <i class="fas fa-check-circle mr-1"></i> Listo para Entrega
                    </button>
                @endif
                <a href="{{ route('restaurante.pedidos.show', $pedido->id) }}"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-eye mr-1"></i> Ver Detalles
                </a>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-receipt text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">No hay pedidos</p>
        </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $pedidos->links() }}
</div>

@push('scripts')
<script>
async function cambiarEstado(pedidoId, estado) {
    const response = await fetch(`/restaurante/pedidos/${pedidoId}/estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ estado })
    });

    const data = await response.json();
    if (data.success) {
        window.location.reload();
    }
}

function rechazarPedido(pedidoId) {
    const motivo = prompt('Motivo del rechazo:');
    if (motivo) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/restaurante/pedidos/${pedidoId}/rechazar`;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="motivo" value="${motivo}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection