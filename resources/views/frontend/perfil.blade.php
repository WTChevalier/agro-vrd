@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Mi Perfil</h1>
    
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Nombre</label>
            <p class="text-gray-900">{{ auth()->user()->name ?? 'Usuario' }}</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Email</label>
            <p class="text-gray-900">{{ auth()->user()->email ?? '' }}</p>
        </div>
        
        <div class="mt-6">
            <a href="{{ url('/admin') }}" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                Ir al Panel Admin
            </a>
        </div>
    </div>
</div>
@endsection