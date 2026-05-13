<?php

use Illuminate\Support\Facades\Route;

// ── Fase 2 ──
use App\Http\Controllers\PublicLandingController;

Route::get("/locale/{locale}/switch", [PublicLandingController::class, "switchLocale"])->name("locale.switch");
Route::get("/", [PublicLandingController::class, "home"])->name("home");


/*
|--------------------------------------------------------------------------
| Web Routes - SazónRD
|--------------------------------------------------------------------------
*/

// Página principal
//FASE2_OLD Route::get("/", function () {
//FASE2_OLD     return view('welcome');
//FASE2_OLD })->name('inicio');

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// ============================================
// RUTAS FRONTEND - SAZONRD
// ============================================

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\RestauranteController;
use App\Http\Controllers\Frontend\CarritoController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\PedidoController;
use App\Http\Controllers\Frontend\PerfilController;

// Páginas públicas
//FASE2_OLD Route::get("/", function () { return view('vertical.home', ['categorias' => \App\Models\Categoria::where('activo', 1)->orderBy('orden')->get(), 'brand_primary' => '#ec4899', 'brand_secondary' => '#831843', 'brand_tagline' => 'Encuentra tu estilo dominicano: belleza, moda y bienestar', 'brand_emoji' => '✨']); })->name('home');
Route::get('/buscar', [HomeController::class, 'buscar'])->name('buscar');

// Restaurantes
Route::get('/restaurantes', [RestauranteController::class, 'index'])->name('restaurantes.index');
Route::get('/restaurantes/{slug}', [RestauranteController::class, 'show'])->name('restaurantes.show');

// Carrito (funciona para guests y autenticados)
Route::prefix('carrito')->name('carrito.')->group(function () {
    Route::get('/', [CarritoController::class, 'index'])->name('index');
    Route::post('/agregar', [CarritoController::class, 'agregar'])->name('agregar');
    Route::put('/{key}', [CarritoController::class, 'actualizar'])->name('actualizar');
    Route::delete('/{key}', [CarritoController::class, 'eliminar'])->name('eliminar');
    Route::delete('/', [CarritoController::class, 'vaciar'])->name('vaciar');
});

// Checkout y Pedidos (requieren autenticación)
Route::middleware(['auth'])->group(function () {
    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'procesar'])->name('checkout.procesar');
    Route::get('/checkout/confirmacion/{codigo}', [CheckoutController::class, 'confirmacion'])->name('checkout.confirmacion');

    // Mis Pedidos
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', [PedidoController::class, 'index'])->name('index');
        Route::get('/{codigo}', [PedidoController::class, 'show'])->name('show');
        Route::post('/{codigo}/cancelar', [PedidoController::class, 'cancelar'])->name('cancelar');
    });

    // Perfil
    Route::prefix('perfil')->name('perfil.')->group(function () {
        Route::get('/', [PerfilController::class, 'index'])->name('index');
        Route::put('/', [PerfilController::class, 'actualizar'])->name('actualizar');
        Route::put('/password', [PerfilController::class, 'cambiarPassword'])->name('password');
        Route::post('/direcciones', [PerfilController::class, 'agregarDireccion'])->name('direcciones.agregar');
        Route::delete('/direcciones/{id}', [PerfilController::class, 'eliminarDireccion'])->name('direcciones.eliminar');
    });
});

// Seguimiento público (no requiere auth)
Route::get('/seguimiento/{codigo}', [PedidoController::class, 'seguimiento'])->name('pedidos.seguimiento');

// ============================================
// RUTAS PANEL RESTAURANTE
// ============================================

use App\Http\Controllers\Restaurante\DashboardController as RestauranteDashboard;
use App\Http\Controllers\Restaurante\PedidoController as RestaurantePedido;
use App\Http\Controllers\Restaurante\MenuController as RestauranteMenu;
use App\Http\Controllers\Restaurante\ConfiguracionController as RestauranteConfig;

Route::prefix('restaurante')->name('restaurante.')->middleware(['auth', 'role:restaurante'])->group(function () {
    Route::get('/', [RestauranteDashboard::class, 'index'])->name('dashboard');

    // Pedidos
    Route::prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', [RestaurantePedido::class, 'index'])->name('index');
        Route::get('/{id}', [RestaurantePedido::class, 'show'])->name('show');
        Route::post('/{id}/estado', [RestaurantePedido::class, 'cambiarEstado'])->name('estado');
        Route::post('/{id}/rechazar', [RestaurantePedido::class, 'rechazar'])->name('rechazar');
    });

    // Menú
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [RestauranteMenu::class, 'index'])->name('index');
        Route::post('/categoria', [RestauranteMenu::class, 'crearCategoria'])->name('crear-categoria');
        Route::post('/producto', [RestauranteMenu::class, 'crearProducto'])->name('crear-producto');
        Route::put('/producto/{id}', [RestauranteMenu::class, 'editarProducto'])->name('editar-producto');
        Route::post('/producto/{id}/toggle', [RestauranteMenu::class, 'toggleDisponibilidad'])->name('toggle-producto');
        Route::delete('/producto/{id}', [RestauranteMenu::class, 'eliminarProducto'])->name('eliminar-producto');
    });

    // Configuración
    Route::get('/configuracion', [RestauranteConfig::class, 'index'])->name('configuracion');
    Route::put('/configuracion', [RestauranteConfig::class, 'actualizar'])->name('configuracion.actualizar');
    Route::post('/toggle-estado', [RestauranteConfig::class, 'toggleEstado'])->name('toggle-estado');
});

// ============================================
// RUTAS PANEL REPARTIDOR
// ============================================

use App\Http\Controllers\Repartidor\DashboardController as RepartidorDashboard;

Route::prefix('repartidor')->name('repartidor.')->middleware(['auth', 'role:repartidor'])->group(function () {
    Route::get('/', [RepartidorDashboard::class, 'index'])->name('dashboard');
    Route::post('/aceptar/{id}', [RepartidorDashboard::class, 'aceptarPedido'])->name('aceptar-pedido');
    Route::get('/activo', [RepartidorDashboard::class, 'pedidoActivo'])->name('pedido.activo');
    Route::post('/confirmar/{id}', [RepartidorDashboard::class, 'confirmarEntrega'])->name('confirmar-entrega');
    Route::post('/ubicacion', [RepartidorDashboard::class, 'actualizarUbicacion'])->name('actualizar-ubicacion');
    Route::get('/historial', [RepartidorDashboard::class, 'historial'])->name('historial');
    Route::get('/perfil', [RepartidorDashboard::class, 'perfil'])->name('perfil');
    Route::post('/toggle-disponibilidad', [RepartidorDashboard::class, 'toggleDisponibilidad'])->name('toggle-disponibilidad');
});
Route::post('/logout', function() { auth()->logout(); return redirect('/'); });
Route::get('/perfil', function() { return view('frontend.perfil'); });

// ─── IdP Cuenta Gurztac ───
Route::middleware('gurztac.auth')->get('/mi-cuenta', function (\Illuminate\Http\Request $request) {
    $claims = $request->attributes->get('gurztac_claims', []);
    return view('mi-cuenta-idp', ['claims' => $claims]);
})->name('mi-cuenta-idp');
