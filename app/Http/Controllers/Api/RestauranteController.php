<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurante;
use App\Models\Categoria;

class RestauranteController extends Controller
{
    public function index(Request $request)
    {
        $restaurantes = Restaurante::where('activo', true)
            ->when($request->categoria, function ($q) use ($request) {
                $q->whereHas('categorias', function ($q2) use ($request) {
                    $q2->where('id', $request->categoria);
                });
            })
            ->when($request->buscar, function ($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->buscar . '%');
            })
            ->when($request->lat && $request->lng, function ($q) use ($request) {
                // Ordenar por distancia (simplificado)
                $q->orderByRaw("
                    (6371 * acos(cos(radians(?)) * cos(radians(latitud)) *
                    cos(radians(longitud) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitud))))
                ", [$request->lat, $request->lng, $request->lat]);
            })
            ->with(['categorias'])
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $restaurantes
        ]);
    }

    public function show($id)
    {
        $restaurante = Restaurante::where('activo', true)
            ->with(['categorias', 'categoriasMenu.productos' => function ($q) {
                $q->where('activo', true)->where('disponible', true);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $restaurante
        ]);
    }

    public function categorias()
    {
        $categorias = Categoria::where('activo', true)
            ->orderBy('orden')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }
}