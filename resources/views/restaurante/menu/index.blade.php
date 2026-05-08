@extends('restaurante.layouts.app')

@section('title', 'Menú')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold">Gestionar Menú</h2>
    <button onclick="document.getElementById('modalCategoria').classList.remove('hidden')"
            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
        <i class="fas fa-plus mr-1"></i> Nueva Categoría
    </button>
</div>

@foreach($categorias as $categoria)
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
            <h3 class="font-semibold text-lg">{{ $categoria->nombre }}</h3>
            <button onclick="abrirModalProducto({{ $categoria->id }})"
                    class="text-blue-600 hover:underline text-sm">
                <i class="fas fa-plus mr-1"></i> Agregar Producto
            </button>
        </div>

        <div class="p-4">
            @forelse($categoria->productos as $producto)
                <div class="flex items-center justify-between py-3 border-b last:border-0">
                    <div class="flex items-center">
                        <img src="{{ $producto->imagen ?? 'https://via.placeholder.com/60' }}"
                             class="w-14 h-14 rounded-lg object-cover mr-4">
                        <div>
                            <p class="font-medium">{{ $producto->nombre }}</p>
                            <p class="text-sm text-gray-500">{{ Str::limit($producto->descripcion, 50) }}</p>
                            <p class="text-green-600 font-bold">RD$ {{ number_format($producto->precio, 0) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="toggleDisponibilidad({{ $producto->id }})"
                                class="px-3 py-1 rounded-full text-sm {{ $producto->disponible ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $producto->disponible ? 'Disponible' : 'Agotado' }}
                        </button>
                        <button class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('restaurante.menu.eliminar-producto', $producto->id) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar este producto?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No hay productos en esta categoría</p>
            @endforelse
        </div>
    </div>
@endforeach

<!-- Modal Nueva Categoría -->
<div id="modalCategoria" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Nueva Categoría</h3>
        <form action="{{ route('restaurante.menu.crear-categoria') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Nombre</label>
                <input type="text" name="nombre" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalCategoria').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg">Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nuevo Producto -->
<div id="modalProducto" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
        <h3 class="text-lg font-semibold mb-4">Nuevo Producto</h3>
        <form action="{{ route('restaurante.menu.crear-producto') }}" method="POST">
            @csrf
            <input type="hidden" name="categoria_menu_id" id="categoriaId">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Nombre *</label>
                    <input type="text" name="nombre" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Descripción</label>
                    <textarea name="descripcion" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Precio *</label>
                    <input type="number" name="precio" required step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Precio Oferta</label>
                    <input type="number" name="precio_oferta" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">URL Imagen</label>
                    <input type="url" name="imagen"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalProducto').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg">Crear</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function abrirModalProducto(categoriaId) {
    document.getElementById('categoriaId').value = categoriaId;
    document.getElementById('modalProducto').classList.remove('hidden');
}

async function toggleDisponibilidad(productoId) {
    const response = await fetch(`/restaurante/menu/producto/${productoId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });
    const data = await response.json();
    if (data.success) {
        window.location.reload();
    }
}
</script>
@endpush
@endsection