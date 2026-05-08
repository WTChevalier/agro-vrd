<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            ['codigo' => 'restaurantes.ver', 'nombre' => 'Ver restaurantes', 'descripcion' => 'Permite ver la lista y detalles de restaurantes', 'modulo' => 'restaurantes'],
            ['codigo' => 'restaurantes.crear', 'nombre' => 'Crear restaurantes', 'descripcion' => 'Permite registrar nuevos restaurantes', 'modulo' => 'restaurantes'],
            ['codigo' => 'restaurantes.editar', 'nombre' => 'Editar restaurantes', 'descripcion' => 'Permite modificar datos de restaurantes', 'modulo' => 'restaurantes'],
            ['codigo' => 'restaurantes.eliminar', 'nombre' => 'Eliminar restaurantes', 'descripcion' => 'Permite eliminar/desactivar restaurantes', 'modulo' => 'restaurantes'],
            ['codigo' => 'pedidos.ver', 'nombre' => 'Ver pedidos', 'descripcion' => 'Permite ver lista de pedidos', 'modulo' => 'pedidos'],
            ['codigo' => 'pedidos.editar', 'nombre' => 'Editar pedidos', 'descripcion' => 'Permite modificar estados y datos de pedidos', 'modulo' => 'pedidos'],
            ['codigo' => 'usuarios.ver', 'nombre' => 'Ver usuarios', 'descripcion' => 'Permite ver lista de usuarios', 'modulo' => 'usuarios'],
            ['codigo' => 'usuarios.crear', 'nombre' => 'Crear usuarios', 'descripcion' => 'Permite crear nuevos usuarios', 'modulo' => 'usuarios'],
            ['codigo' => 'usuarios.editar', 'nombre' => 'Editar usuarios', 'descripcion' => 'Permite modificar datos de usuarios', 'modulo' => 'usuarios'],
            ['codigo' => 'configuracion.ver', 'nombre' => 'Ver configuración', 'descripcion' => 'Permite ver configuración del sistema', 'modulo' => 'configuracion'],
            ['codigo' => 'configuracion.editar', 'nombre' => 'Editar configuración', 'descripcion' => 'Permite modificar configuración del sistema', 'modulo' => 'configuracion'],
        ];

        foreach ($permisos as $permiso) {
            DB::table('permisos')->insert([
                'codigo' => $permiso['codigo'],
                'nombre' => $permiso['nombre'],
                'descripcion' => $permiso['descripcion'],
                'modulo' => $permiso['modulo'],
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}