<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RestauranteController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\WebhookController;

// ============================================
// API PÚBLICA
// ============================================

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Restaurantes (público)
Route::get('/categorias', [RestauranteController::class, 'categorias']);
Route::get('/restaurantes', [RestauranteController::class, 'index']);
Route::get('/restaurantes/{id}', [RestauranteController::class, 'show']);

// Tracking público
Route::get('/tracking/{codigo}', [PedidoController::class, 'tracking']);

// Webhooks (sin auth)
Route::post('/webhooks/cardnet', [WebhookController::class, 'cardnet']);
Route::post('/webhooks/azul', [WebhookController::class, 'azul']);

// ============================================
// API AUTENTICADA
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // Usuario
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pedidos
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']);
    Route::post('/pedidos/{id}/cancelar', [PedidoController::class, 'cancelar']);
});