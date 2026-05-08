<?php

namespace App\Http\Controllers\Restaurante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\CategoriaMenu;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index()
    {
        $restaurante = auth()->user()->restaurante;

        $categorias = CategoriaMenu::where('restaurante_id', $restaurante->id)
            ->with(['productos' => function ($q) {
                $q->orderBy('orden');
            }])
            ->orderBy('orden')
            ->get();

        return view('restaurante.menu.index', compact('categorias'));
    }

    public function crearCategoria(Request $request)
    {
        $restaurante = auth()->user()->restaurante;

        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $orden = CategoriaMenu::where('restaurante_id', $restaurante->id)->max('orden') + 1;

        CategoriaMenu::create([
            'restaurante_id' => $restaurante->id,
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre),
            'orden' => $orden,
            'activo' => true,
        ]);

        return back()->with('success', 'Categoría creada');
    }

    public function crearProducto(Request $request)
    {
        $restaurante = auth()->user()->restaurante;

        $request->validate([
            'categoria_menu_id' => 'required|exists:categorias_menu,id',
            'nombre' => 'required|string|max:200',
            'precio' => 'required|numeric|min:0',
        ]);

        $orden = Producto::where('categoria_menu_id', $request->categoria_menu_id)->max('orden') + 1;

        Producto::create([
            'restaurante_id' => $restaurante->id,
            'categoria_menu_id' => $request->categoria_menu_id,
            'nombre' => $request->nombre,
            'slug' => Str::slug($request->nombre) . '-' . uniqid(),
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'precio_oferta' => $request->precio_oferta,
            'imagen' => $request->imagen,
            'orden' => $orden,
            'activo' => true,
            'disponible' => true,
        ]);

        return back()->with('success', 'Producto creado');
    }

    public function editarProducto(Request $request, $id)
    {
        $restaurante = auth()->user()->restaurante;

        $producto = Producto::where('restaurante_id', $restaurante->id)->findOrFail($id);

        $producto->update($request->only([
            'nombre', 'descripcion', 'precio', 'precio_oferta', 'imagen', 'activo', 'disponible'
        ]));

        return back()->with('success', 'Producto actualizado');
    }

    public function toggleDisponibilidad($id)
    {
        $restaurante = auth()->user()->restaurante;

        $producto = Producto::where('restaurante_id', $restaurante->id)->findOrFail($id);

        $producto->update(['disponible' => !$producto->disponible]);

        return response()->json([
            'success' => true,
            'disponible' => $producto->disponible
        ]);
    }

    public function eliminarProducto($id)
    {
        $restaurante = auth()->user()->restaurante;

        Producto::where('restaurante_id', $restaurante->id)->where('id', $id)->delete();

        return back()->with('success', 'Producto eliminado');
    }
}