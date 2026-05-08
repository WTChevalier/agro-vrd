<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurante;
use App\Models\Categoria;
use App\Models\CategoriaMenu;

class RestauranteController extends Controller
{
    public function index(Request $request)
    {
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();

        $restaurantes = Restaurante::where('activo', true)
            ->when($request->categoria, function ($q) use ($request) {
                $q->whereHas('categorias', function ($q2) use ($request) {
                    $q2->where('slug', $request->categoria);
                });
            })
            ->when($request->delivery_gratis, function ($q) {
                $q->where('costo_delivery', 0);
            })
            ->when($request->promocion, function ($q) {
                $q->where('tiene_promocion', true);
            })
            ->when($request->orden, function ($q) use ($request) {
                switch ($request->orden) {
                    case 'calificacion':
                        $q->orderByDesc('calificacion_promedio');
                        break;
                    case 'tiempo':
                        $q->orderBy('tiempo_entrega_estimado');
                        break;
                    default:
                        $q->orderByDesc('destacado')->orderByDesc('calificacion_promedio');
                }
            }, function ($q) {
                $q->orderByDesc('destacado')->orderByDesc('calificacion_promedio');
            })
            ->with(['categorias'])
            ->paginate(12);

        $titulo = $request->categoria
            ? Categoria::where('slug', $request->categoria)->first()->nombre ?? 'Restaurantes'
            : 'Todos los Restaurantes';

        return view('frontend.restaurantes.index', compact('restaurantes', 'categorias', 'titulo'));
    }

    public function show($slug)
    {
        $restaurante = Restaurante::where('slug', $slug)
            ->where('activo', true)
            ->with(['productos', 'categorias'])
            ->firstOrFail();

        $categorias = CategoriaMenu::where('restaurante_id', $restaurante->id)
            ->where('activo', true)
            ->orderBy('orden')
            ->with(['productos' => function ($q) {
                $q->where('activo', true)->orderBy('orden');
            }])
            ->get();

        return view('frontend.restaurantes.show', compact('restaurante', 'categorias'));
    }
}