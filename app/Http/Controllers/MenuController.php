<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\Plato;
use App\Models\CategoriaMenu;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Controlador del Menú
 *
 * Maneja la visualización del menú de los restaurantes.
 */
class MenuController extends Controller
{
    /**
     * Mostrar el menú completo de un restaurante.
     */
    public function index(Request $request, Restaurante $restaurante): View
    {
        if (!$restaurante->activo) {
            abort(404, 'Restaurante no encontrado');
        }

        $validado = $request->validate([
            'categoria' => 'nullable|integer|exists:categorias_menu,id',
            'buscar' => 'nullable|string|max:100',
            'filtro' => 'nullable|string|in:destacados,vegetariano,vegano,sin_gluten,picante',
        ]);

        // Obtener categorías del menú
        $categorias = CategoriaMenu::query()
            ->where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        // Query base de platos
        $queryPlatos = Plato::query()
            ->where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->where('disponible', true)
            ->with(['categoria', 'gruposOpciones.opciones', 'imagenPrincipal']);

        // Filtrar por categoría
        if (!empty($validado['categoria'])) {
            $queryPlatos->where('categoria_menu_id', $validado['categoria']);
        }

        // Búsqueda
        if (!empty($validado['buscar'])) {
            $busqueda = $validado['buscar'];
            $queryPlatos->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('descripcion', 'like', "%{$busqueda}%");
            });
        }

        // Filtros especiales
        if (!empty($validado['filtro'])) {
            match ($validado['filtro']) {
                'destacados' => $queryPlatos->where('destacado', true),
                'vegetariano' => $queryPlatos->where('es_vegetariano', true),
                'vegano' => $queryPlatos->where('es_vegano', true),
                'sin_gluten' => $queryPlatos->where('sin_gluten', true),
                'picante' => $queryPlatos->where('es_picante', true),
                default => null,
            };
        }

        $platos = $queryPlatos
            ->orderBy('orden')
            ->get()
            ->groupBy('categoria_menu_id');

        // Platos destacados
        $platosDestacados = Plato::query()
            ->where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->where('disponible', true)
            ->where('destacado', true)
            ->with(['categoria', 'imagenPrincipal'])
            ->limit(6)
            ->get();

        // Combos disponibles
        $combos = $restaurante->combos()
            ->where('activo', true)
            ->vigentes()
            ->with(['platos', 'imagen'])
            ->get();

        // Verificar si el restaurante está abierto
        $estaAbierto = $restaurante->estaAbiertoAhora();

        return view('restaurantes.menu', [
            'restaurante' => $restaurante,
            'categorias' => $categorias,
            'platos' => $platos,
            'platosDestacados' => $platosDestacados,
            'combos' => $combos,
            'estaAbierto' => $estaAbierto,
            'filtros' => $validado,
        ]);
    }

    /**
     * Obtener los detalles de un plato específico (API).
     */
    public function mostrarPlato(Request $request, Restaurante $restaurante, int $platoId): JsonResponse
    {
        if (!$restaurante->activo) {
            abort(404, 'Restaurante no encontrado');
        }

        $plato = Plato::query()
            ->where('id', $platoId)
            ->where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->with([
                'categoria',
                'imagenes',
                'gruposOpciones' => function ($query) {
                    $query->where('activo', true)
                        ->orderBy('orden');
                },
                'gruposOpciones.opciones' => function ($query) {
                    $query->where('activo', true)
                        ->orderBy('orden');
                },
            ])
            ->firstOrFail();

        return response()->json([
            'plato' => $plato,
            'restaurante' => [
                'id' => $restaurante->id,
                'nombre' => $restaurante->nombre,
                'esta_abierto' => $restaurante->estaAbiertoAhora(),
                'pedido_minimo' => $restaurante->pedido_minimo,
            ],
        ]);
    }

    /**
     * Obtener platos de una categoría específica (API).
     */
    public function platosPorCategoria(Request $request, Restaurante $restaurante, int $categoriaId): JsonResponse
    {
        if (!$restaurante->activo) {
            abort(404, 'Restaurante no encontrado');
        }

        $categoria = CategoriaMenu::query()
            ->where('id', $categoriaId)
            ->where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->firstOrFail();

        $platos = Plato::query()
            ->where('restaurante_id', $restaurante->id)
            ->where('categoria_menu_id', $categoriaId)
            ->where('activo', true)
            ->where('disponible', true)
            ->with(['gruposOpciones.opciones', 'imagenPrincipal'])
            ->orderBy('orden')
            ->get();

        return response()->json([
            'categoria' => $categoria,
            'platos' => $platos,
        ]);
    }

    /**
     * Buscar platos en el menú del restaurante (API).
     */
    public function buscar(Request $request, Restaurante $restaurante): JsonResponse
    {
        $validado = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        if (!$restaurante->activo) {
            abort(404, 'Restaurante no encontrado');
        }

        $consulta = $validado['q'];

        $platos = Plato::query()
            ->where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->where('disponible', true)
            ->where(function ($q) use ($consulta) {
                $q->where('nombre', 'like', "%{$consulta}%")
                    ->orWhere('descripcion', 'like', "%{$consulta}%");
            })
            ->with(['categoria', 'gruposOpciones.opciones', 'imagenPrincipal'])
            ->limit(20)
            ->get();

        return response()->json([
            'platos' => $platos,
            'consulta' => $consulta,
        ]);
    }

    /**
     * Obtener opciones de un plato (API).
     */
    public function opcionesPlato(int $platoId): JsonResponse
    {
        $plato = Plato::query()
            ->where('id', $platoId)
            ->where('activo', true)
            ->with([
                'gruposOpciones' => function ($query) {
                    $query->where('activo', true)
                        ->orderBy('orden');
                },
                'gruposOpciones.opciones' => function ($query) {
                    $query->where('activo', true)
                        ->orderBy('orden');
                },
            ])
            ->firstOrFail();

        return response()->json([
            'plato_id' => $plato->id,
            'nombre' => $plato->nombre,
            'grupos_opciones' => $plato->gruposOpciones,
        ]);
    }

    /**
     * Obtener reseñas de un plato (API).
     */
    public function resenasPlato(int $platoId): JsonResponse
    {
        $plato = Plato::findOrFail($platoId);

        $resenas = $plato->resenas()
            ->with('usuario:id,nombre,apellido,avatar')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'plato_id' => $plato->id,
            'calificacion_promedio' => $plato->calificacion,
            'total_resenas' => $plato->total_resenas,
            'resenas' => $resenas,
        ]);
    }
}
