@extends('layouts.app')

@section('title', 'Mi Perfil - SazónRD')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <i class="fas fa-user mr-2"></i> Mi Perfil
    </h1>

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Información Personal -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Información Personal</h3>
            <form action="{{ route('perfil.actualizar') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input type="text" name="name" value="{{ $usuario->name }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" value="{{ $usuario->email }}" disabled
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="{{ $usuario->telefono }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary">
                    </div>
                    <button type="submit" class="w-full bg-sazon-primary text-white py-2 rounded-lg hover:bg-red-600 transition">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- Cambiar Contraseña -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Cambiar Contraseña</h3>
            <form action="{{ route('perfil.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña Actual</label>
                        <input type="password" name="password_actual"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                        <input type="password" name="password"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-sazon-primary">
                    </div>
                    <button type="submit" class="w-full bg-sazon-secondary text-white py-2 rounded-lg hover:bg-opacity-90 transition">
                        Cambiar Contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Direcciones -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-gray-800">Mis Direcciones</h3>
            <button type="button" onclick="document.getElementById('nuevaDireccion').classList.toggle('hidden')"
                    class="text-sazon-primary hover:underline text-sm">
                <i class="fas fa-plus mr-1"></i> Agregar
            </button>
        </div>

        <!-- Formulario nueva dirección -->
        <div id="nuevaDireccion" class="hidden mb-4 p-4 bg-gray-50 rounded-lg">
            <form action="{{ route('perfil.direcciones.agregar') }}" method="POST">
                @csrf
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Etiqueta</label>
                        <select name="etiqueta" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="Casa">Casa</option>
                            <option value="Trabajo">Trabajo</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                        <input type="text" name="sector" placeholder="Ej: Piantini"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Completa *</label>
                    <input type="text" name="direccion_completa" required placeholder="Calle, número, edificio..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" name="referencia" placeholder="Cerca de..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="bg-sazon-primary text-white px-4 py-2 rounded-lg hover:bg-red-600">
                    Guardar Dirección
                </button>
            </form>
        </div>

        <!-- Lista de direcciones -->
        <div class="space-y-3">
            @forelse($direcciones as $direccion)
                <div class="flex items-start justify-between p-3 border rounded-lg">
                    <div>
                        <span class="font-medium">{{ $direccion->etiqueta }}</span>
                        <p class="text-sm text-gray-600">{{ $direccion->direccion_completa }}</p>
                        @if($direccion->referencia)
                            <p class="text-xs text-gray-400">Ref: {{ $direccion->referencia }}</p>
                        @endif
                    </div>
                    <form action="{{ route('perfil.direcciones.eliminar', $direccion->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700"
                                onclick="return confirm('¿Eliminar esta dirección?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No tienes direcciones guardadas</p>
            @endforelse
        </div>
    </div>
</div>
@endsection