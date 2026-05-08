<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder Principal de SazónRD
 *
 * Ejecuta todos los seeders en el orden correcto para
 * inicializar el sistema con los datos necesarios
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // DATOS BASE DEL SISTEMA
        // Estos seeders deben ejecutarse siempre, en este orden
        // =====================================================================

        $this->call([
            // 1. Geografía de República Dominicana
            ProvinciasSeeder::class,

            // 2. Permisos del sistema (antes de roles)
            PermisosSeeder::class,

            // 3. Roles con permisos asignados
            RolesSeeder::class,

            // 4. Módulos y funciones del sistema
            ModulosSistemaSeeder::class,

            // 5. Planes de suscripción
            PlanesSeeder::class,

            // 6. Niveles de confianza (interno)
            NivelesConfianzaSeeder::class,

            // 7. Certificaciones (público)
            CertificacionesSeeder::class,

            // 8. Materiales promocionales y kits
            MaterialesSeeder::class,

            // 9. Configuración global del sistema
            ConfiguracionGlobalSeeder::class,
        ]);

        // =====================================================================
        // DATOS DE DESARROLLO/PRUEBAS
        // Solo se ejecutan en ambiente local o de desarrollo
        // =====================================================================
        if (app()->environment('local', 'development', 'staging')) {
            $this->call([
                // DatosDemo\UsuariosDemoSeeder::class,
                // DatosDemo\RestaurantesDemoSeeder::class,
                // DatosDemo\PedidosDemoSeeder::class,
            ]);
        }
    }
}
