<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Roles
 *
 * Define los roles del sistema SazónRD con sus permisos
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // =====================================================================
            // SUPER ADMIN - Acceso total
            // =====================================================================
            [
                'codigo' => 'super_admin',
                'nombre' => 'Super Administrador',
                'descripcion' => 'Acceso total al sistema. Solo para fundadores y CTO.',
                'nivel' => 100,
                'color' => '#DC2626', // Rojo
                'permisos' => ['*'], // Todos los permisos
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // ADMIN - Administrador general
            // =====================================================================
            [
                'codigo' => 'admin',
                'nombre' => 'Administrador',
                'descripcion' => 'Administrador general de la plataforma. Acceso a la mayoría de funciones excepto configuración crítica.',
                'nivel' => 90,
                'color' => '#EA580C', // Naranja
                'permisos' => [
                    'restaurantes.*',
                    'pedidos.*',
                    'usuarios.*',
                    'personal.ver',
                    'personal.editar',
                    'finanzas.ver',
                    'finanzas.ver_detalle',
                    'finanzas.exportar',
                    'cobranzas.*',
                    'planes.*',
                    'suscripciones.gestionar',
                    'certificaciones.*',
                    'confianza.*',
                    'materiales.*',
                    'soporte.*',
                    'reportes.*',
                    'zonas.*',
                    'tareas.*',
                    'auditoria.ver',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // CONTADOR - Finanzas y cobranzas
            // =====================================================================
            [
                'codigo' => 'contador',
                'nombre' => 'Contador',
                'descripcion' => 'Gestión financiera completa. Acceso a reportes, pagos y cobranzas.',
                'nivel' => 70,
                'color' => '#059669', // Verde
                'permisos' => [
                    'finanzas.*',
                    'cobranzas.ver',
                    'cobranzas.asignar',
                    'restaurantes.ver',
                    'pedidos.ver',
                    'pedidos.ver_todos',
                    'reportes.*',
                    'suscripciones.gestionar',
                    'auditoria.ver',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // SUPERVISOR - Supervisa personal de campo
            // =====================================================================
            [
                'codigo' => 'supervisor',
                'nombre' => 'Supervisor',
                'descripcion' => 'Supervisa verificadores, cobradores y soporte. Acceso a reportes de su zona.',
                'nivel' => 60,
                'color' => '#7C3AED', // Violeta
                'permisos' => [
                    'restaurantes.ver',
                    'restaurantes.verificar',
                    'pedidos.ver',
                    'personal.ver',
                    'personal.asignar_zonas',
                    'cobranzas.ver',
                    'cobranzas.asignar',
                    'certificaciones.ver',
                    'certificaciones.evaluar',
                    'confianza.ver',
                    'materiales.ver',
                    'materiales.entregar',
                    'soporte.ver',
                    'soporte.atender',
                    'reportes.ver',
                    'tareas.*',
                    'auditoria.ver',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // VERIFICADOR - Verifica restaurantes en campo
            // =====================================================================
            [
                'codigo' => 'verificador',
                'nombre' => 'Verificador',
                'descripcion' => 'Personal de campo que verifica restaurantes, entrega materiales y evalúa certificaciones.',
                'nivel' => 40,
                'color' => '#0891B2', // Cyan
                'permisos' => [
                    'restaurantes.ver',
                    'restaurantes.verificar',
                    'certificaciones.ver',
                    'certificaciones.evaluar',
                    'materiales.ver',
                    'materiales.entregar',
                    'tareas.ver_propias',
                    'tareas.completar',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // COBRADOR - Cobra pagos en campo
            // =====================================================================
            [
                'codigo' => 'cobrador',
                'nombre' => 'Cobrador',
                'descripcion' => 'Personal de campo que cobra pagos pendientes a restaurantes.',
                'nivel' => 40,
                'color' => '#CA8A04', // Amarillo oscuro
                'permisos' => [
                    'restaurantes.ver',
                    'cobranzas.ver',
                    'cobranzas.registrar',
                    'finanzas.registrar_pagos',
                    'tareas.ver_propias',
                    'tareas.completar',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // SOPORTE - Atención al cliente y restaurantes
            // =====================================================================
            [
                'codigo' => 'soporte',
                'nombre' => 'Soporte',
                'descripcion' => 'Atiende tickets de soporte de clientes y restaurantes.',
                'nivel' => 30,
                'color' => '#2563EB', // Azul
                'permisos' => [
                    'restaurantes.ver',
                    'pedidos.ver',
                    'pedidos.ver_todos',
                    'usuarios.ver',
                    'soporte.*',
                    'tareas.ver_propias',
                    'tareas.completar',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // MARKETING - Promociones y comunicación
            // =====================================================================
            [
                'codigo' => 'marketing',
                'nombre' => 'Marketing',
                'descripcion' => 'Gestiona promociones, comunicaciones y materiales de marketing.',
                'nivel' => 30,
                'color' => '#DB2777', // Rosa
                'permisos' => [
                    'restaurantes.ver',
                    'materiales.ver',
                    'materiales.gestionar',
                    'certificaciones.ver',
                    'reportes.ver',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],

            // =====================================================================
            // SOLO LECTURA - Para auditoría externa
            // =====================================================================
            [
                'codigo' => 'solo_lectura',
                'nombre' => 'Solo Lectura',
                'descripcion' => 'Acceso de solo lectura para auditoría externa o inversionistas.',
                'nivel' => 10,
                'color' => '#6B7280', // Gris
                'permisos' => [
                    'restaurantes.ver',
                    'pedidos.ver',
                    'finanzas.ver',
                    'reportes.ver',
                    'auditoria.ver',
                ],
                'es_sistema' => true,
                'puede_eliminarse' => false,
            ],
        ];

        foreach ($roles as $rol) {
            $permisos = $rol['permisos'];
            unset($rol['permisos']);

            DB::table('roles')->insert([
                ...$rol,
                'permisos' => json_encode($permisos),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear relaciones rol_permiso para roles con permisos específicos
        $this->asignarPermisosARoles();
    }

    /**
     * Asigna permisos a roles en la tabla pivot
     */
    private function asignarPermisosARoles(): void
    {
        $roles = DB::table('roles')->get();
        $todosLosPermisos = DB::table('permisos')->pluck('id', 'codigo');

        foreach ($roles as $rol) {
            $permisosRol = json_decode($rol->permisos, true);

            // Si tiene '*' significa todos los permisos
            if (in_array('*', $permisosRol)) {
                foreach ($todosLosPermisos as $codigo => $permisoId) {
                    DB::table('rol_permiso')->insert([
                        'rol_id' => $rol->id,
                        'permiso_id' => $permisoId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                continue;
            }

            foreach ($permisosRol as $patronPermiso) {
                // Si termina en .* significa todos los permisos del módulo
                if (str_ends_with($patronPermiso, '.*')) {
                    $modulo = str_replace('.*', '', $patronPermiso);
                    $permisosModulo = DB::table('permisos')
                        ->where('codigo', 'like', $modulo . '.%')
                        ->pluck('id');

                    foreach ($permisosModulo as $permisoId) {
                        DB::table('rol_permiso')->insertOrIgnore([
                            'rol_id' => $rol->id,
                            'permiso_id' => $permisoId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Permiso específico
                    if (isset($todosLosPermisos[$patronPermiso])) {
                        DB::table('rol_permiso')->insertOrIgnore([
                            'rol_id' => $rol->id,
                            'permiso_id' => $todosLosPermisos[$patronPermiso],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
