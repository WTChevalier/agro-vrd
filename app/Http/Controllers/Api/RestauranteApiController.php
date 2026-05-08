<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurante;
use App\Models\TipoCocina;
use App\Models\CategoriaRestaurante;
use App\Http\Resources\RestauranteResource;
use App\Http\Resources\RestauranteCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controlador API de Restaurantes
 *
 * Endpoints públicos para consultar restaurantes.
 */
class RestauranteApiController extends Controller
{
    /**
     * Listar restaurantes con filtros y paginación.
     */
    public function index(Request $request): JsonResponse
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

        if ($request->filled('calificacion_minima')) {
            $query->where('calificacion', '>=', $request->input('calificacion_minima'));
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
            case 'popularidad':
                $query->orderByDesc('total_pedidos');
                break;
            default:
                $query->orderByDesc('destacado')->orderByDesc('calificacion');
        }

        $restaurantes = $query->paginate($request->input('por_pagina', 15));

        return response()->json([
            'exito' => true,
            'datos' => RestauranteResource::collection($restaurantes),
            'paginacion' => [
                'total' => $restaurantes->total(),
                'por_pagina' => $restaurantes->perPage(),
                'pagina_actual' => $restaurantes->currentPage(),
                'ultima_pagina' => $restaurantes->lastPage(),
            ],
        ]);
    }

    /**
     * Obtener restaurantes destacados.
     */
    public function destacados(): JsonResponse
    {
        $restaurantes = Restaurante::query()
            ->where('activo', true)
            ->where('destacado', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->limit(10)
            ->get();

        return response()->json([
            'exito' => true,
            'datos' => RestauranteResource::collection($restaurantes),
        ]);
    }

    /**
     * Obtener restaurantes cercanos a una ubicación.
     */
    public function cercaDeMi(Request $request): JsonResponse
    {
        $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'radio' => 'nullable|numeric|min:1|max:50',
        ]);

        $latitud = $request->input('latitud');
        $longitud = $request->input('longitud');
        $radio = $request->input('radio', 5);

        $restaurantes = Restaurante::cercaDe($latitud, $longitud, $radio)
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->limit(20)
            ->get();

        return response()->json([
            'exito' => true,
            'datos' => RestauranteResource::collection($restaurantes),
            'parametros' => [
                'latitud' => $latitud,
                'longitud' => $longitud,
                'radio_km' => $radio,
            ],
        ]);
    }

    /**
     * Buscar restaurantes por texto.
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $consulta = $request->input('q');

        $restaurantes = Restaurante::query()
            ->where('activo', true)
            ->where(function ($q) use ($consulta) {
                $q->where('nombre', 'like', "%{$consulta}%")
                    ->orWhere('descripcion', 'like', "%{$consulta}%")
                    ->orWhereHas('tiposCocina', function ($q2) use ($consulta) {
                        $q2->where('nombre', 'like', "%{$consulta}%");
                    });
            })
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->limit(20)
            ->get();

        return response()->json([
            'exito' => true,
            'consulta' => $consulta,
            'datos' => RestauranteResource::collection($restaurantes),
        ]);
    }

    /**
     * Obtener restaurantes por categoría.
     */
    public function porCategoria(CategoriaRestaurante $categoria): JsonResponse
    {
        $restaurantes = $categoria->restaurantes()
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->paginate(15);

        return response()->json([
            'exito' => true,
            'categoria' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'slug' => $categoria->slug,
            ],
            'datos' => RestauranteResource::collection($restaurantes),
        ]);
    }

    /**
     * Obtener restaurantes por tipo de cocina.
     */
    public function porTipoCocina(TipoCocina $tipoCocina): JsonResponse
    {
        $restaurantes = $tipoCocina->restaurantes()
            ->where('activo', true)
            ->with(['tiposCocina', 'imagenPrincipal'])
            ->orderByDesc('calificacion')
            ->paginate(15);

        return response()->json([
            'exito' => true,
            'tipo_cocina' => [
                'id' => $tipoCocina->id,
                'nombre' => $tipoCocina->nombre,
                'slug' => $tipoCocina->slug,
            ],
            'datos' => RestauranteResource::collection($restaurantes),
        ]);
    }

    /**
     * Mostrar detalle de un restaurante.
     */
    public function mostrar(Restaurante $restaurante): JsonResponse
    {
        if (!$restaurante->activo) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Restaurante no encontrado',
            ], 404);
        }

        $restaurante->load([
            'tiposCocina',
            'horarios',
            'zonasDelivery.sector',
            'imagenes',
        ]);

        return response()->json([
            'exito' => true,
            'datos' => new RestauranteResource($restaurante),
            'esta_abierto' => $restaurante->estaAbiertoAhora(),
        ]);
    }

    /**
     * Obtener reseñas de un restaurante.
     */
    public function resenas(Request $request, Restaurante $restaurante): JsonResponse
    {
        if (!$restaurante->activo) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Restaurante no encontrado',
            ], 404);
        }

        $resenas = $restaurante->resenas()
            ->with('usuario:id,nombre,apellido,avatar')
            ->orderByDesc('created_at')
            ->paginate($request->input('por_pagina', 10));

        return response()->json([
            'exito' => true,
            'estadisticas' => [
                'calificacion_promedio' => $restaurante->calificacion,
                'total_resenas' => $restaurante->total_resenas,
            ],
            'resenas' => $resenas,
        ]);
    }

    /**
     * Obtener horarios de un restaurante.
     */
    public function horarios(Restaurante $restaurante): JsonResponse
    {
        if (!$restaurante->activo) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Restaurante no encontrado',
            ], 404);
        }

        $horarios = $restaurante->horarios()
            ->orderBy('dia_semana')
            ->get()
            ->groupBy('dia_semana');

        $diasSemana = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];

        $horariosFormateados = [];
        foreach ($diasSemana as $numero => $nombre) {
            $horariosFormateados[] = [
                'dia' => $nombre,
                'numero_dia' => $numero,
                'horarios' => $horarios->get($numero, collect())->map(function ($h) {
                    return [
                        'apertura' => $h->hora_apertura,
                        'cierre' => $h->hora_cierre,
                    ];
                }),
                'cerrado' => !$horarios->has($numero),
            ];
        }

        return response()->json([
            'exito' => true,
            'esta_abierto' => $restaurante->estaAbiertoAhora(),
            'horarios' => $horariosFormateados,
        ]);
    }

    /**
     * Obtener todos los tipos de cocina.
     */
    public function tiposCocina(): JsonResponse
    {
        $tiposCocina = TipoCocina::query()
            ->where('activo', true)
            ->withCount(['restaurantes' => function ($q) {
                $q->where('activo', true);
            }])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'exito' => true,
            'datos' => $tiposCocina,
        ]);
    }

    /**
     * Obtener todas las categorías de restaurantes.
     */
    public function categoriasRestaurante(): JsonResponse
    {
        $categorias = CategoriaRestaurante::query()
            ->where('activo', true)
            ->withCount(['restaurantes' => function ($q) {
                $q->where('activo', true);
            }])
            ->orderBy('orden')
            ->get();

        return response()->json([
            'exito' => true,
            'datos' => $categorias,
        ]);
    }
}
