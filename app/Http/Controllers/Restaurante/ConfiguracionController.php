<?php

namespace App\Http\Controllers\Restaurante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $restaurante = auth()->user()->restaurante;

        return view('restaurante.configuracion', compact('restaurante'));
    }

    public function actualizar(Request $request)
    {
        $restaurante = auth()->user()->restaurante;

        $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'direccion' => 'required|string',
            'telefono' => 'required|string',
            'horario_apertura' => 'required',
            'horario_cierre' => 'required',
            'tiempo_entrega_estimado' => 'required|integer|min:10',
            'pedido_minimo' => 'required|numeric|min:0',
            'costo_delivery' => 'required|numeric|min:0',
        ]);

        $restaurante->update($request->only([
            'nombre', 'descripcion', 'direccion', 'telefono',
            'horario_apertura', 'horario_cierre',
            'tiempo_entrega_estimado', 'pedido_minimo', 'costo_delivery'
        ]));

        return back()->with('success', 'Configuración actualizada');
    }

    public function toggleEstado()
    {
        $restaurante = auth()->user()->restaurante;

        $restaurante->update(['abierto' => !$restaurante->abierto]);

        return response()->json([
            'success' => true,
            'abierto' => $restaurante->abierto
        ]);
    }
}