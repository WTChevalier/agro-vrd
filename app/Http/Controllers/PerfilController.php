<?php

namespace App\Http\Controllers;

use App\Models\DireccionUsuario;
use App\Models\Favorito;
use App\Models\TransaccionBilletera;
use App\Models\TransaccionLealtad;
use App\Models\NivelLealtad;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador del Perfil de Usuario
 *
 * Maneja todas las operaciones del perfil: datos personales,
 * direcciones, favoritos, billetera y programa de lealtad.
 */
class PerfilController extends Controller
{
    /**
     * Mostrar la página principal del perfil.
     */
    public function index(): View
    {
        $usuario = auth()->user()->load(['nivelLealtad']);

        $estadisticas = [
            'total_pedidos' => $usuario->pedidos()->count(),
            'pedidos_mes' => $usuario->pedidos()
                ->whereMonth('created_at', now()->month)
                ->count(),
            'total_gastado' => $usuario->pedidos()
                ->whereHas('estado', fn($q) => $q->where('codigo', 'entregado'))
                ->sum('total'),
            'restaurantes_favoritos' => $usuario->favoritos()
                ->where('tipo_favorito', 'restaurante')
                ->count(),
        ];

        return view('perfil.index', compact('usuario', 'estadisticas'));
    }

    /**
     * Actualizar los datos personales.
     */
    public function actualizar(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'genero' => 'nullable|in:masculino,femenino,otro,prefiero_no_decir',
        ]);

        auth()->user()->update($request->only([
            'nombre',
            'apellido',
            'telefono',
            'fecha_nacimiento',
            'genero',
        ]));

        return back()->with('exito', 'Perfil actualizado correctamente');
    }

    // =========================================================================
    // DIRECCIONES
    // =========================================================================

    /**
     * Listar las direcciones del usuario.
     */
    public function direcciones(): View
    {
        $direcciones = auth()->user()->direcciones()
            ->with(['sector.municipio.provincia'])
            ->orderByDesc('es_predeterminada')
            ->orderByDesc('created_at')
            ->get();

        return view('perfil.direcciones', compact('direcciones'));
    }

    /**
     * Guardar una nueva dirección.
     */
    public function guardarDireccion(Request $request): RedirectResponse
    {
        $request->validate([
            'etiqueta' => 'required|string|max:50',
            'direccion_linea1' => 'required|string|max:255',
            'direccion_linea2' => 'nullable|string|max:255',
            'sector_id' => 'required|exists:sectores,id',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'instrucciones_entrega' => 'nullable|string|max:500',
            'telefono_contacto' => 'nullable|string|max:20',
            'es_predeterminada' => 'boolean',
        ]);

        // Si es predeterminada, quitar el flag de las demás
        if ($request->boolean('es_predeterminada')) {
            auth()->user()->direcciones()->update(['es_predeterminada' => false]);
        }

        auth()->user()->direcciones()->create($request->all());

        return back()->with('exito', 'Dirección agregada correctamente');
    }

    /**
     * Actualizar una dirección existente.
     */
    public function actualizarDireccion(Request $request, DireccionUsuario $direccion): RedirectResponse
    {
        $this->autorizarDireccion($direccion);

        $request->validate([
            'etiqueta' => 'required|string|max:50',
            'direccion_linea1' => 'required|string|max:255',
            'direccion_linea2' => 'nullable|string|max:255',
            'sector_id' => 'required|exists:sectores,id',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'instrucciones_entrega' => 'nullable|string|max:500',
            'telefono_contacto' => 'nullable|string|max:20',
        ]);

        $direccion->update($request->all());

        return back()->with('exito', 'Dirección actualizada correctamente');
    }

    /**
     * Eliminar una dirección.
     */
    public function eliminarDireccion(DireccionUsuario $direccion): RedirectResponse
    {
        $this->autorizarDireccion($direccion);

        $direccion->delete();

        return back()->with('exito', 'Dirección eliminada');
    }

    /**
     * Establecer una dirección como predeterminada.
     */
    public function establecerDireccionPredeterminada(DireccionUsuario $direccion): RedirectResponse
    {
        $this->autorizarDireccion($direccion);

        // Quitar predeterminada de todas
        auth()->user()->direcciones()->update(['es_predeterminada' => false]);

        // Establecer la nueva
        $direccion->update(['es_predeterminada' => true]);

        return back()->with('exito', 'Dirección predeterminada actualizada');
    }

    // =========================================================================
    // FAVORITOS
    // =========================================================================

    /**
     * Listar los favoritos del usuario.
     */
    public function favoritos(Request $request): View
    {
        $tipo = $request->input('tipo', 'restaurante');

        $favoritos = auth()->user()->favoritos()
            ->where('tipo_favorito', $tipo)
            ->with(['favoritable'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('perfil.favoritos', compact('favoritos', 'tipo'));
    }

    /**
     * Alternar un favorito (agregar/quitar).
     */
    public function alternarFavorito(string $tipo, int $id): RedirectResponse
    {
        $tiposPermitidos = ['restaurante', 'plato'];
        if (!in_array($tipo, $tiposPermitidos)) {
            abort(400, 'Tipo de favorito no válido');
        }

        $modelClass = $tipo === 'restaurante'
            ? \App\Models\Restaurante::class
            : \App\Models\Plato::class;

        $favorito = auth()->user()->favoritos()
            ->where('tipo_favorito', $tipo)
            ->where('favoritable_id', $id)
            ->where('favoritable_type', $modelClass)
            ->first();

        if ($favorito) {
            $favorito->delete();
            $mensaje = 'Eliminado de favoritos';
        } else {
            auth()->user()->favoritos()->create([
                'tipo_favorito' => $tipo,
                'favoritable_id' => $id,
                'favoritable_type' => $modelClass,
            ]);
            $mensaje = 'Agregado a favoritos';
        }

        return back()->with('exito', $mensaje);
    }

    // =========================================================================
    // BILLETERA DIGITAL
    // =========================================================================

    /**
     * Mostrar la billetera del usuario.
     */
    public function billetera(): View
    {
        $usuario = auth()->user();
        $saldo = $usuario->saldo_billetera;

        $ultimasTransacciones = $usuario->transaccionesBilletera()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('perfil.billetera', compact('saldo', 'ultimasTransacciones'));
    }

    /**
     * Listar transacciones de la billetera.
     */
    public function transaccionesBilletera(Request $request): View
    {
        $query = auth()->user()->transaccionesBilletera()
            ->orderByDesc('created_at');

        // Filtrar por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtrar por fecha
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        $transacciones = $query->paginate(20)->withQueryString();

        return view('perfil.billetera-transacciones', compact('transacciones'));
    }

    // =========================================================================
    // PROGRAMA DE LEALTAD
    // =========================================================================

    /**
     * Mostrar el programa de lealtad del usuario.
     */
    public function puntosLealtad(): View
    {
        $usuario = auth()->user()->load('nivelLealtad');

        // Puntos actuales
        $puntos = $usuario->puntos_lealtad;

        // Nivel actual y siguiente
        $nivelActual = $usuario->nivelLealtad;
        $siguienteNivel = NivelLealtad::where('puntos_minimos', '>', $puntos)
            ->orderBy('puntos_minimos')
            ->first();

        // Progreso hacia el siguiente nivel
        $progreso = 0;
        if ($nivelActual && $siguienteNivel) {
            $puntosEnNivel = $puntos - $nivelActual->puntos_minimos;
            $puntosParaSiguiente = $siguienteNivel->puntos_minimos - $nivelActual->puntos_minimos;
            $progreso = min(100, ($puntosEnNivel / $puntosParaSiguiente) * 100);
        }

        // Todos los niveles
        $niveles = NivelLealtad::orderBy('puntos_minimos')->get();

        // Últimas transacciones
        $ultimasTransacciones = $usuario->transaccionesLealtad()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('perfil.puntos', compact(
            'puntos',
            'nivelActual',
            'siguienteNivel',
            'progreso',
            'niveles',
            'ultimasTransacciones'
        ));
    }

    /**
     * Historial de transacciones de puntos.
     */
    public function historialPuntos(Request $request): View
    {
        $query = auth()->user()->transaccionesLealtad()
            ->orderByDesc('created_at');

        // Filtrar por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        $transacciones = $query->paginate(20)->withQueryString();

        return view('perfil.puntos-historial', compact('transacciones'));
    }

    /**
     * Canjear puntos por recompensas.
     */
    public function canjearPuntos(Request $request): RedirectResponse
    {
        $request->validate([
            'recompensa_id' => 'required|exists:recompensas_lealtad,id',
        ]);

        $recompensa = \App\Models\RecompensaLealtad::findOrFail($request->input('recompensa_id'));

        // Verificar puntos suficientes
        if (auth()->user()->puntos_lealtad < $recompensa->puntos_requeridos) {
            return back()->with('error', 'No tienes suficientes puntos para esta recompensa');
        }

        // Verificar nivel requerido
        if ($recompensa->nivel_minimo_id) {
            $nivelUsuario = auth()->user()->nivelLealtad;
            $nivelRequerido = $recompensa->nivelMinimo;

            if (!$nivelUsuario || $nivelUsuario->puntos_minimos < $nivelRequerido->puntos_minimos) {
                return back()->with('error', 'Tu nivel no es suficiente para esta recompensa');
            }
        }

        // Procesar canje
        try {
            auth()->user()->canjearPuntosLealtad($recompensa);

            return back()->with('exito', "¡Recompensa canjeada! {$recompensa->nombre}");

        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'No se pudo canjear la recompensa');
        }
    }

    // =========================================================================
    // NOTIFICACIONES
    // =========================================================================

    /**
     * Mostrar configuración de notificaciones.
     */
    public function notificaciones(): View
    {
        $configuracion = auth()->user()->configuracionNotificaciones ?? [
            'email_pedidos' => true,
            'email_promociones' => true,
            'push_pedidos' => true,
            'push_promociones' => false,
            'sms_pedidos' => false,
        ];

        return view('perfil.notificaciones', compact('configuracion'));
    }

    /**
     * Actualizar configuración de notificaciones.
     */
    public function actualizarNotificaciones(Request $request): RedirectResponse
    {
        $request->validate([
            'email_pedidos' => 'boolean',
            'email_promociones' => 'boolean',
            'push_pedidos' => 'boolean',
            'push_promociones' => 'boolean',
            'sms_pedidos' => 'boolean',
        ]);

        auth()->user()->update([
            'configuracion_notificaciones' => $request->only([
                'email_pedidos',
                'email_promociones',
                'push_pedidos',
                'push_promociones',
                'sms_pedidos',
            ]),
        ]);

        return back()->with('exito', 'Preferencias de notificaciones actualizadas');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Verificar que la dirección pertenece al usuario.
     */
    protected function autorizarDireccion(DireccionUsuario $direccion): void
    {
        if ($direccion->usuario_id !== auth()->id()) {
            abort(403, 'No tienes permiso para modificar esta dirección');
        }
    }
}
