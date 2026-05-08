<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\TipoCocina;
use App\Models\CategoriaRestaurante;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de la Página Principal
 *
 * Maneja la página de inicio y la búsqueda general de la aplicación.
 */
class InicioController extends Controller
{
    /**
     * Mostrar la página principal.
     */
    public function index(Request $request): View
    {
        // Restaurantes destacados
        $restaurantesDestacados = Restaurante::query()
            ->where('activo', true)
            ->where('destacado', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->limit(8)
            ->get();

        // Restaurantes cerca del usuario (si tiene ubicación)
        $restaurantesCercanos = collect();
        if ($request->has(['latitud', 'longitud'])) {
            $restaurantesCercanos = Restaurante::cercaDe(
                $request->input('latitud'),
                $request->input('longitud'),
                5 // Radio de 5 km
            )
                ->where('activo', true)
                ->with(['tiposCocina', 'imagenPrincipal'])
                ->limit(6)
                ->get();
        }

        // Tipos de cocina populares
        $tiposCocinaPopulares = TipoCocina::query()
            ->where('activo', true)
            ->withCount('restaurantes')
            ->orderByDesc('restaurantes_count')
            ->limit(10)
            ->get();

        // Categorías de restaurantes
        $categorias = CategoriaRestaurante::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        // Restaurantes nuevos (últimos 30 días)
        $restaurantesNuevos = Restaurante::query()
            ->where('activo', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        // Restaurantes mejor calificados
        $mejorCalificados = Restaurante::query()
            ->where('activo', true)
            ->where('total_resenas', '>=', 10)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->limit(6)
            ->get();

        return view('inicio', compact(
            'restaurantesDestacados',
            'restaurantesCercanos',
            'tiposCocinaPopulares',
            'categorias',
            'restaurantesNuevos',
            'mejorCalificados'
        ));
    }

    /**
     * Buscar restaurantes y platos.
     */
    public function buscar(Request $request): View
    {
        $consulta = $request->input('q', '');
        $filtros = [
            'tipo_cocina' => $request->input('tipo_cocina'),
            'categoria' => $request->input('categoria'),
            'precio_minimo' => $request->input('precio_minimo'),
            'precio_maximo' => $request->input('precio_maximo'),
            'calificacion_minima' => $request->input('calificacion_minima'),
            'ordenar_por' => $request->input('ordenar_por', 'relevancia'),
            'solo_abiertos' => $request->boolean('solo_abiertos'),
            'con_delivery' => $request->boolean('con_delivery'),
        ];

        // Buscar restaurantes
        $queryRestaurantes = Restaurante::query()
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal']);

        // Aplicar búsqueda por texto
        if (!empty($consulta)) {
            $queryRestaurantes->where(function ($q) use ($consulta) {
                $q->where('nombre', 'like', "%{$consulta}%")
                    ->orWhere('descripcion', 'like', "%{$consulta}%")
                    ->orWhereHas('tiposCocina', function ($q2) use ($consulta) {
                        $q2->where('nombre', 'like', "%{$consulta}%");
                    });
            });
        }

        // Aplicar filtro de tipo de cocina
        if (!empty($filtros['tipo_cocina'])) {
            $queryRestaurantes->whereHas('tiposCocina', function ($q) use ($filtros) {
                $q->where('slug', $filtros['tipo_cocina']);
            });
        }

        // Aplicar filtro de categoría
        if (!empty($filtros['categoria'])) {
            $queryRestaurantes->whereHas('categorias', function ($q) use ($filtros) {
                $q->where('slug', $filtros['categoria']);
            });
        }

        // Filtrar por calificación mínima
        if (!empty($filtros['calificacion_minima'])) {
            $queryRestaurantes->where('calificacion', '>=', $filtros['calificacion_minima']);
        }

        // Solo restaurantes abiertos
        if ($filtros['solo_abiertos']) {
            $queryRestaurantes->abiertosAhora();
        }

        // Solo con delivery
        if ($filtros['con_delivery']) {
            $queryRestaurantes->where('ofrece_delivery', true);
        }

        // Ordenar resultados
        switch ($filtros['ordenar_por']) {
            case 'calificacion':
                $queryRestaurantes->orderByDesc('calificacion');
                break;
            case 'popularidad':
                $queryRestaurantes->orderByDesc('total_pedidos');
                break;
            case 'tiempo_entrega':
                $queryRestaurantes->orderBy('tiempo_entrega_estimado');
                break;
            case 'precio_bajo':
                $queryRestaurantes->orderBy('precio_promedio');
                break;
            case 'precio_alto':
                $queryRestaurantes->orderByDesc('precio_promedio');
                break;
            default:
                // Relevancia: combinar varios factores
                $queryRestaurantes->orderByDesc('calificacion')
                    ->orderByDesc('total_pedidos');
        }

        $restaurantes = $queryRestaurantes->paginate(12)->withQueryString();

        // Tipos de cocina para filtros
        $tiposCocina = TipoCocina::where('activo', true)
            ->orderBy('nombre')
            ->get();

        // Categorías para filtros
        $categorias = CategoriaRestaurante::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('buscar', compact(
            'consulta',
            'filtros',
            'restaurantes',
            'tiposCocina',
            'categorias'
        ));
    }
}
