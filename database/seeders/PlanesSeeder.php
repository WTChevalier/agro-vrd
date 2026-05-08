<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Planes de Suscripción
 *
 * Define los planes disponibles para restaurantes en SazónRD
 */
class PlanesSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            // =====================================================================
            // PLAN VITRINA - Básico/Gratuito
            // Solo presencia en la plataforma
            // =====================================================================
            [
                'codigo' => 'vitrina',
                'nombre' => 'Vitrina',
                'descripcion' => 'Presencia básica en SazónRD. Ideal para comenzar.',
                'descripcion_corta' => 'Presencia básica',
                'precio_mensual' => 0,
                'precio_anual' => 0,
                'es_gratuito' => true,
                'es_destacado' => false,
                'color' => '#6B7280',
                'icono' => 'store',
                'funciones' => [
                    'permitir_resenas' => true,
                    'responder_resenas' => true,
                    'ver_estadisticas_basicas' => true,
                ],
                'limites' => [
                    'productos_menu' => 20,
                    'fotos_galeria' => 5,
                    'categorias_menu' => 3,
                ],
                'beneficios' => [
                    'Perfil del restaurante en SazónRD',
                    'Hasta 20 productos en el menú',
                    'Hasta 5 fotos en galería',
                    'Recibir y responder reseñas',
                    'Estadísticas básicas',
                    'Soporte por email',
                ],
                'orden' => 1,
            ],

            // =====================================================================
            // PLAN BÁSICO - Entrada al sistema
            // =====================================================================
            [
                'codigo' => 'basico',
                'nombre' => 'Básico',
                'descripcion' => 'Para restaurantes que quieren más visibilidad y herramientas.',
                'descripcion_corta' => 'Más visibilidad',
                'precio_mensual' => 1500,
                'precio_anual' => 15000, // 2 meses gratis
                'es_gratuito' => false,
                'es_destacado' => false,
                'color' => '#3B82F6',
                'icono' => 'trending-up',
                'funciones' => [
                    'permitir_resenas' => true,
                    'responder_resenas' => true,
                    'ver_estadisticas_basicas' => true,
                    'crear_cupones' => true,
                    'max_cupones_activos' => 2,
                ],
                'limites' => [
                    'productos_menu' => 50,
                    'fotos_galeria' => 15,
                    'categorias_menu' => 8,
                    'cupones_mes' => 2,
                ],
                'beneficios' => [
                    'Todo lo del plan Vitrina',
                    'Hasta 50 productos en el menú',
                    'Hasta 15 fotos en galería',
                    'Crear hasta 2 cupones por mes',
                    'Prioridad en búsquedas de zona',
                    'Kit básico de materiales',
                    'Soporte prioritario',
                ],
                'orden' => 2,
            ],

            // =====================================================================
            // PLAN PROFESIONAL - Para negocios serios
            // =====================================================================
            [
                'codigo' => 'profesional',
                'nombre' => 'Profesional',
                'descripcion' => 'Para restaurantes que buscan crecer con todas las herramientas.',
                'descripcion_corta' => 'Crecimiento completo',
                'precio_mensual' => 3500,
                'precio_anual' => 35000, // 2 meses gratis
                'es_gratuito' => false,
                'es_destacado' => true,
                'color' => '#F97316',
                'icono' => 'rocket',
                'funciones' => [
                    'permitir_resenas' => true,
                    'responder_resenas' => true,
                    'ver_estadisticas_basicas' => true,
                    'ver_estadisticas_avanzadas' => true,
                    'exportar_reportes' => true,
                    'crear_cupones' => true,
                    'max_cupones_activos' => 5,
                    'reservar_mesas' => true,
                ],
                'limites' => [
                    'productos_menu' => 150,
                    'fotos_galeria' => 50,
                    'categorias_menu' => 20,
                    'cupones_mes' => 10,
                ],
                'beneficios' => [
                    'Todo lo del plan Básico',
                    'Productos ilimitados en menú',
                    'Hasta 50 fotos en galería',
                    'Hasta 10 cupones por mes',
                    'Sistema de reservaciones',
                    'Estadísticas avanzadas',
                    'Exportar reportes',
                    'Destacado en página de inicio',
                    'Kit completo de materiales',
                    'Placa de certificación',
                    'Soporte telefónico',
                ],
                'orden' => 3,
            ],

            // =====================================================================
            // PLAN PREMIUM - Para cadenas y grandes restaurantes
            // =====================================================================
            [
                'codigo' => 'premium',
                'nombre' => 'Premium',
                'descripcion' => 'Para cadenas de restaurantes y negocios de alto volumen.',
                'descripcion_corta' => 'Sin límites',
                'precio_mensual' => 7500,
                'precio_anual' => 75000, // 2 meses gratis
                'es_gratuito' => false,
                'es_destacado' => false,
                'color' => '#7C3AED',
                'icono' => 'crown',
                'funciones' => [
                    'permitir_resenas' => true,
                    'responder_resenas' => true,
                    'ver_estadisticas_basicas' => true,
                    'ver_estadisticas_avanzadas' => true,
                    'exportar_reportes' => true,
                    'crear_cupones' => true,
                    'max_cupones_activos' => -1, // Ilimitado
                    'reservar_mesas' => true,
                    'acumular_puntos' => true,
                    'recibir_pedidos' => true, // Cuando esté disponible
                    'pedidos_programados' => true,
                ],
                'limites' => [
                    'productos_menu' => -1, // Ilimitado
                    'fotos_galeria' => -1,
                    'categorias_menu' => -1,
                    'cupones_mes' => -1,
                    'sucursales' => 5,
                ],
                'beneficios' => [
                    'Todo lo del plan Profesional',
                    'Todo ILIMITADO',
                    'Hasta 5 sucursales',
                    'Programa de lealtad integrado',
                    'API para integración',
                    'Acceso anticipado a nuevas funciones',
                    'Materiales premium personalizados',
                    'Gerente de cuenta dedicado',
                    'Soporte 24/7',
                ],
                'orden' => 4,
            ],

            // =====================================================================
            // PLAN CADENA - Para franquicias
            // =====================================================================
            [
                'codigo' => 'cadena',
                'nombre' => 'Cadena',
                'descripcion' => 'Solución empresarial para franquicias y cadenas nacionales.',
                'descripcion_corta' => 'Empresarial',
                'precio_mensual' => null, // Precio personalizado
                'precio_anual' => null,
                'es_gratuito' => false,
                'es_destacado' => false,
                'color' => '#DC2626',
                'icono' => 'building',
                'funciones' => [
                    '*' => true, // Todas las funciones
                ],
                'limites' => [
                    'productos_menu' => -1,
                    'fotos_galeria' => -1,
                    'categorias_menu' => -1,
                    'cupones_mes' => -1,
                    'sucursales' => -1, // Ilimitadas
                ],
                'beneficios' => [
                    'Todo lo del plan Premium',
                    'Sucursales ilimitadas',
                    'Dashboard centralizado',
                    'Reportes consolidados',
                    'Branding personalizado',
                    'Integración con sistemas existentes',
                    'Capacitación para personal',
                    'SLA garantizado',
                    'Precio negociable',
                ],
                'orden' => 5,
            ],
        ];

        foreach ($planes as $plan) {
            $funciones = $plan['funciones'];
            $limites = $plan['limites'];
            $beneficios = $plan['beneficios'];

            unset($plan['funciones'], $plan['limites'], $plan['beneficios']);

            DB::table('planes')->insert([
                ...$plan,
                'funciones' => json_encode($funciones),
                'limites' => json_encode($limites),
                'beneficios' => json_encode($beneficios),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
