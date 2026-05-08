<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.Usuario.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('pedido.{pedidoId}', function ($user, $pedidoId) {
    return true; // Agregar lógica de autorización según necesidad
});

Broadcast::channel('restaurante.{restauranteId}', function ($user, $restauranteId) {
    return $user->restaurantes()->where('id', $restauranteId)->exists();
});
