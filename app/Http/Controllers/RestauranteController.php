<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\TipoCocina;
use App\Models\CategoriaRestaurante;
use App\Models\ResenaRestaurante;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de Restaurantes
 *
 * Maneja la visualización de restaurantes para los usuarios finales.
 */
class RestauranteController extends Controller
{
    /**
     * Listar todos los restaurantes activos.
     */
    public function index(Request $request): View
    {
        $query = Restaurante::query()
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal']);

        // Filtros opcionales
        if ($request->filled('tipo_cocina')) {
            $query->whereHas('tiposCocina', function ($q) use ($request) {
                $q->where('slug', $request->input('tipo_cocina'));
            });
        }

        if ($request->filled('categoria')) {
            $query->whereHas('categorias', function ($q) use ($request) {
                $q->where('slug', $request->input('categoria'));
            });
        }

        if ($request->boolean('solo_abiertos')) {
            $query->abiertosAhora();
        }

        if ($request->boolean('con_delivery')) {
            $query->where('ofrece_delivery', true);
        }

        // Ordenamiento
        $ordenar = $request->input('ordenar', 'destacados');
        switch ($ordenar) {
            case 'calificacion':
                $query->orderByDesc('calificacion');
                break;
            case 'nombre':
                $query->orderBy('nombre');
                break;
            case 'tiempo_entrega':
                $query->orderBy('tiempo_entrega_estimado');
                break;
            default:
                $query->orderByDesc('destacado')->orderByDesc('calificacion');
        }

        $restaurantes = $query->paginate(12)->withQueryString();

        // Datos para filtros
        $tiposCocina = TipoCocina::where('activo', true)->orderBy('nombre')->get();
        $categorias = CategoriaRestaurante::where('activo', true)->orderBy('nombre')->get();

        return view('restaurantes.index', compact(
            'restaurantes',
            'tiposCocina',
            'categorias',
            'ordenar'
        ));
    }

    /**
     * Mostrar restaurantes cerca de la ubicación del usuario.
     */
    public function cercaDeMi(Request $request): View
    {
        $latitud = $request->input('latitud');
        $longitud = $request->input('longitud');
        $radio = $request->input('radio', 5); // Radio por defecto: 5 km

        $restaurantes = collect();

        if ($latitud && $longitud) {
            $restaurantes = Restaurante::cercaDe($latitud, $longitud, $radio)
                ->where('activo', true)
                ->with(['tiposCocina', 'imagenPrincipal'])
                ->paginate(12)
                ->withQueryString();
        }

        return view('restaurantes.cerca-de-mi', compact(
            'restaurantes',
            'latitud',
            'longitud',
            'radio'
        ));
    }

    /**
     * Mostrar restaurantes por categoría.
     */
    public function porCategoria(CategoriaRestaurante $categoria): View
    {
        $restaurantes = $categoria->restaurantes()
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->paginate(12);

        return view('restaurantes.por-categoria', compact(
            'categoria',
            'restaurantes'
        ));
    }

    /**
     * Mostrar restaurantes por tipo de cocina.
     */
    public function porTipoCocina(TipoCocina $tipoCocina): View
    {
        $restaurantes = $tipoCocina->restaurantes()
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->paginate(12);

        return view('restaurantes.por-tipo-cocina', compact(
            'tipoCocina',
            'restaurantes'
        ));
    }

    /**
     * Mostrar el detalle de un restaurante.
     */
    public function mostrar(Restaurante $restaurante): View
    {
        // Verificar que el restaurante esté activo
        if (!$restaurante->activo) {
            abort(404, 'Restaurante no encontrado');
        }

        // Cargar relaciones necesarias
        $restaurante->load([
            'tiposCocina',
            'horarios',
            'zonasDelivery',
            'imagenes',
            'propietario',
        ]);

        // Categorías del menú con platos activos
        $categoriasMenu = $restaurante->categoriasMenu()
            ->where('activo', true)
            ->with(['platos' => function ($query) {
                $query->where('disponible', true)
                    ->orderBy('orden')
                    ->with('imagenPrincipal');
            }])
            ->orderBy('orden')
            ->get();

        // Combos activos
        $combos = $restaurante->combos()
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_inicio')
                    ->orWhere('fecha_inicio', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', now());
            })
            ->with(['platos', 'imagen'])
            ->get();

        // Últimas reseñas
        $ultimasResenas = $restaurante->resenas()
            ->with('usuario')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Estadísticas de reseñas
        $estadisticasResenas = [
            'total' => $restaurante->total_resenas,
            'promedio' => $restaurante->calificacion,
            'distribucion' => ResenaRestaurante::where('restaurante_id', $restaurante->id)
                ->selectRaw('calificacion, COUNT(*) as cantidad')
                ->groupBy('calificacion')
                ->pluck('cantidad', 'calificacion')
                ->toArray(),
        ];

        // Información de delivery
        $infoDelivery = [
            'ofrece_delivery' => $restaurante->ofrece_delivery,
            'tiempo_estimado' => $restaurante->tiempo_entrega_estimado,
            'costo_minimo' => $restaurante->pedido_minimo,
            'zonas' => $restaurante->zonasDelivery,
        ];

        // Verificar si está abierto
        $estaAbierto = $restaurante->estaAbiertoAhora();
        $proximoHorario = null;
        if (!$estaAbierto) {
            $proximoHorario = $restaurante->obtenerProximoHorarioApertura();
        }

        return view('restaurantes.mostrar', compact(
            'restaurante',
            'categoriasMenu',
            'combos',
            'ultimasResenas',
            'estadisticasResenas',
            'infoDelivery',
            'estaAbierto',
            'proximoHorario'
        ));
    }

    /**
     * Mostrar las reseñas de un restaurante.
     */
    public function resenas(Restaurante $restaurante, Request $request): View
    {
        if (!$restaurante->activo) {
            abort(404, 'Restaurante no encontrado');
        }

        $query = $restaurante->resenas()
            ->with(['usuario', 'imagenes']);

        // Filtrar por calificación
        if ($request->filled('calificacion')) {
            $query->where('calificacion', $request->input('calificacion'));
        }

        // Ordenar
        $ordenar = $request->input('ordenar', 'recientes');
        switch ($ordenar) {
            case 'mayor_calificacion':
                $query->orderByDesc('calificacion');
                break;
            case 'menor_calificacion':
                $query->orderBy('calificacion');
                break;
            case 'mas_utiles':
                $query->orderByDesc('votos_utiles');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $resenas = $query->paginate(10)->withQueryString();

        // Estadísticas
        $estadisticas = [
            'total' => $restaurante->total_resenas,
            'promedio' => $restaurante->calificacion,
            'distribucion' => ResenaRestaurante::where('restaurante_id', $restaurante->id)
                ->selectRaw('calificacion, COUNT(*) as cantidad')
                ->groupBy('calificacion')
                ->orderByDesc('calificacion')
                ->pluck('cantidad', 'calificacion')
                ->toArray(),
        ];

        return view('restaurantes.resenas', compact(
            'restaurante',
            'resenas',
            'estadisticas',
            'ordenar'
        ));
    }
}
