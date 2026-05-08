<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurante;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;

class HomeController extends Controller
{
    public function index()
    {
        $categorias = Categoria::where('activo', true)
            ->orderBy('orden')
            ->take(8)
            ->get();

        $restaurantesDestacados = Restaurante::where('activo', true)
            ->where('destacado', true)
            ->with(['categorias'])
            ->orderByDesc('calificacion_promedio')
            ->take(8)
            ->get();

        $promociones = Promocion::where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->with('restaurante')
            ->take(6)
            ->get();

        $platosPopulares = Producto::where('activo', true)
            ->where('es_popular', true)
            ->with('restaurante')
            ->orderByDesc('total_vendidos')
            ->take(12)
            ->get();

        return view('frontend.home', compact(
            'categorias',
            'restaurantesDestacados',
            'promociones',
            'platosPopulares'
        ));
    }

    public function buscar(Request $request)
    {
        $query = $request->get('q');
        $ubicacion = $request->get('ubicacion');

        $restaurantes = Restaurante::where('activo', true)
            ->when($query, function ($q) use ($query) {
                $q->where('nombre', 'like', "%{$query}%")
                  ->orWhere('descripcion', 'like', "%{$query}%");
            })
            ->with(['categorias'])
            ->paginate(12);

        $productos = Producto::where('activo', true)
            ->where('nombre', 'like', "%{$query}%")
            ->with('restaurante')
            ->take(10)
            ->get();

        return view('frontend.busqueda', compact('restaurantes', 'productos', 'query'));
    }
}