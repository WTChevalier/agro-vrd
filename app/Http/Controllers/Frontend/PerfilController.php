<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DireccionUsuario;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();
        $direcciones = DireccionUsuario::where('usuario_id', $usuario->id)->get();

        return view('frontend.perfil.index', compact('usuario', 'direcciones'));
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ]);

        $usuario = auth()->user();
        $usuario->update([
            'name' => $request->name,
            'telefono' => $request->telefono,
        ]);

        return back()->with('success', 'Perfil actualizado');
    }

    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'password_actual' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $usuario = auth()->user();

        if (!Hash::check($request->password_actual, $usuario->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual es incorrecta']);
        }

        $usuario->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Contraseña actualizada');
    }

    public function agregarDireccion(Request $request)
    {
        $request->validate([
            'etiqueta' => 'required|string|max:50',
            'direccion_completa' => 'required|string|max:500',
        ]);

        DireccionUsuario::create([
            'usuario_id' => auth()->id(),
            'etiqueta' => $request->etiqueta,
            'sector' => $request->sector,
            'direccion_completa' => $request->direccion_completa,
            'referencia' => $request->referencia,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
        ]);

        return back()->with('success', 'Dirección agregada');
    }

    public function eliminarDireccion($id)
    {
        DireccionUsuario::where('id', $id)
            ->where('usuario_id', auth()->id())
            ->delete();

        return back()->with('success', 'Dirección eliminada');
    }
}