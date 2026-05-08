<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Módulos del Sistema
 *
 * Define los módulos que pueden activarse/desactivarse globalmente
 * y las funciones específicas de cada módulo
 */
class ModulosSistemaSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // MÓDULOS DEL SISTEMA
        // =====================================================================
        $modulos = [
            [
                'codigo' => 'pedidos_online',
                'nombre' => 'Pedidos Online',
                'descripcion' => 'Permite a los clientes realizar pedidos a través de la plataforma',
                'icono' => 'shopping-cart',
                'color' => '#F97316',
                'activo_global' => false, // Inicialmente desactivado
                'requiere_configuracion' => true,
                'orden' => 1,
            ],
            [
                'codigo' => 'delivery',
                'nombre' => 'Delivery',
                'descripcion' => 'Sistema de entregas a domicilio',
                'icono' => 'truck',
                'color' => '#10B981',
                'activo_global' => false,
                'requiere_configuracion' => true,
                'orden' => 2,
            ],
            [
                'codigo' => 'pagos_online',
                'nombre' => 'Pagos Online',
                'descripcion' => 'Procesamiento de pagos con tarjeta y otros métodos digitales',
                'icono' => 'credit-card',
                'color' => '#3B82F6',
                'activo_global' => false,
                'requiere_configuracion' => true,
                'orden' => 3,
            ],
            [
                'codigo' => 'reservaciones',
                'nombre' => 'Reservaciones',
                'descripcion' => 'Sistema de reservación de mesas',
                'icono' => 'calendar',
                'color' => '#8B5CF6',
                'activo_global' => false,
                'requiere_configuracion' => true,
                'orden' => 4,
            ],
            [
                'codigo' => 'resenas',
                'nombre' => 'Reseñas',
                'descripcion' => 'Sistema de reseñas y calificaciones',
                'icono' => 'star',
                'color' => '#EAB308',
                'activo_global' => true, // Activado por defecto
                'requiere_configuracion' => false,
                'orden' => 5,
            ],
            [
                'codigo' => 'promociones',
                'nombre' => 'Promociones',
                'descripcion' => 'Sistema de cupones y promociones',
                'icono' => 'percent',
                'color' => '#EC4899',
                'activo_global' => true,
                'requiere_configuracion' => false,
                'orden' => 6,
            ],
            [
                'codigo' => 'programa_lealtad',
                'nombre' => 'Programa de Lealtad',
                'descripcion' => 'Sistema de puntos y recompensas para clientes',
                'icono' => 'gift',
                'color' => '#6366F1',
                'activo_global' => false,
                'requiere_configuracion' => true,
                'orden' => 7,
            ],
            [
                'codigo' => 'notificaciones_push',
                'nombre' => 'Notificaciones Push',
                'descripcion' => 'Envío de notificaciones push a la app móvil',
                'icono' => 'bell',
                'color' => '#14B8A6',
                'activo_global' => false,
                'requiere_configuracion' => true,
                'orden' => 8,
            ],
            [
                'codigo' => 'chat_soporte',
                'nombre' => 'Chat de Soporte',
                'descripcion' => 'Chat en vivo con soporte',
                'icono' => 'message-circle',
                'color' => '#0EA5E9',
                'activo_global' => false,
                'requiere_configuracion' => true,
                'orden' => 9,
            ],
            [
                'codigo' => 'analytics',
                'nombre' => 'Analytics Avanzado',
                'descripcion' => 'Métricas y análisis avanzado para restaurantes',
                'icono' => 'bar-chart',
                'color' => '#78716C',
                'activo_global' => true,
                'requiere_configuracion' => false,
                'orden' => 10,
            ],
        ];

        foreach ($modulos as $modulo) {
            DB::table('modulos_sistema')->insert([
                ...$modulo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =====================================================================
        // FUNCIONES DEL SISTEMA
        // =====================================================================
        $funciones = [
            // --- FUNCIONES DE PEDIDOS ---
            [
                'modulo_codigo' => 'pedidos_online',
                'codigo' => 'recibir_pedidos',
                'nombre' => 'Recibir Pedidos',
                'descripcion' => 'Permite recibir pedidos de clientes',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'pedidos_online',
                'codigo' => 'pedidos_programados',
                'nombre' => 'Pedidos Programados',
                'descripcion' => 'Permite programar pedidos para hora específica',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],
            [
                'modulo_codigo' => 'pedidos_online',
                'codigo' => 'limite_pedidos_hora',
                'nombre' => 'Límite de Pedidos por Hora',
                'descripcion' => 'Cantidad máxima de pedidos que puede recibir por hora',
                'tipo' => 'numero',
                'valor_defecto' => '20',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 3,
            ],

            // --- FUNCIONES DE DELIVERY ---
            [
                'modulo_codigo' => 'delivery',
                'codigo' => 'delivery_propio',
                'nombre' => 'Delivery Propio',
                'descripcion' => 'Usa repartidores propios del restaurante',
                'tipo' => 'booleano',
                'valor_defecto' => 'true',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'delivery',
                'codigo' => 'delivery_plataforma',
                'nombre' => 'Delivery SazónRD',
                'descripcion' => 'Usa repartidores de la plataforma',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],
            [
                'modulo_codigo' => 'delivery',
                'codigo' => 'rastreo_tiempo_real',
                'nombre' => 'Rastreo en Tiempo Real',
                'descripcion' => 'Muestra ubicación del repartidor al cliente',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 3,
            ],
            [
                'modulo_codigo' => 'delivery',
                'codigo' => 'radio_maximo_km',
                'nombre' => 'Radio Máximo (km)',
                'descripcion' => 'Distancia máxima para entregas',
                'tipo' => 'numero',
                'valor_defecto' => '10',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 4,
            ],

            // --- FUNCIONES DE PAGOS ---
            [
                'modulo_codigo' => 'pagos_online',
                'codigo' => 'pago_tarjeta',
                'nombre' => 'Pago con Tarjeta',
                'descripcion' => 'Acepta pagos con tarjeta de crédito/débito',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'pagos_online',
                'codigo' => 'pago_transferencia',
                'nombre' => 'Pago por Transferencia',
                'descripcion' => 'Acepta transferencias bancarias',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],
            [
                'modulo_codigo' => 'pagos_online',
                'codigo' => 'pago_efectivo',
                'nombre' => 'Pago en Efectivo',
                'descripcion' => 'Acepta pago en efectivo contra entrega',
                'tipo' => 'booleano',
                'valor_defecto' => 'true',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 3,
            ],

            // --- FUNCIONES DE RESERVACIONES ---
            [
                'modulo_codigo' => 'reservaciones',
                'codigo' => 'reservar_mesas',
                'nombre' => 'Reservar Mesas',
                'descripcion' => 'Permite reservar mesas online',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'reservaciones',
                'codigo' => 'capacidad_mesas',
                'nombre' => 'Capacidad de Mesas',
                'descripcion' => 'Número total de mesas disponibles',
                'tipo' => 'numero',
                'valor_defecto' => '10',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],

            // --- FUNCIONES DE RESEÑAS ---
            [
                'modulo_codigo' => 'resenas',
                'codigo' => 'permitir_resenas',
                'nombre' => 'Permitir Reseñas',
                'descripcion' => 'Los clientes pueden dejar reseñas',
                'tipo' => 'booleano',
                'valor_defecto' => 'true',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'resenas',
                'codigo' => 'responder_resenas',
                'nombre' => 'Responder Reseñas',
                'descripcion' => 'El restaurante puede responder reseñas',
                'tipo' => 'booleano',
                'valor_defecto' => 'true',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],

            // --- FUNCIONES DE PROMOCIONES ---
            [
                'modulo_codigo' => 'promociones',
                'codigo' => 'crear_cupones',
                'nombre' => 'Crear Cupones',
                'descripcion' => 'El restaurante puede crear sus propios cupones',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'promociones',
                'codigo' => 'max_cupones_activos',
                'nombre' => 'Máximo Cupones Activos',
                'descripcion' => 'Cantidad máxima de cupones activos simultáneos',
                'tipo' => 'numero',
                'valor_defecto' => '3',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],

            // --- FUNCIONES DE LEALTAD ---
            [
                'modulo_codigo' => 'programa_lealtad',
                'codigo' => 'acumular_puntos',
                'nombre' => 'Acumular Puntos',
                'descripcion' => 'Los clientes acumulan puntos por compras',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => true,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'programa_lealtad',
                'codigo' => 'puntos_por_peso',
                'nombre' => 'Puntos por Peso',
                'descripcion' => 'Cantidad de puntos por cada RD$ gastado',
                'tipo' => 'numero',
                'valor_defecto' => '1',
                'configurable_zona' => true,
                'configurable_restaurante' => false,
                'orden' => 2,
            ],

            // --- FUNCIONES DE ANALYTICS ---
            [
                'modulo_codigo' => 'analytics',
                'codigo' => 'ver_estadisticas_basicas',
                'nombre' => 'Estadísticas Básicas',
                'descripcion' => 'Ver estadísticas de ventas y pedidos',
                'tipo' => 'booleano',
                'valor_defecto' => 'true',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 1,
            ],
            [
                'modulo_codigo' => 'analytics',
                'codigo' => 'ver_estadisticas_avanzadas',
                'nombre' => 'Estadísticas Avanzadas',
                'descripcion' => 'Ver métricas detalladas y comparativas',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 2,
            ],
            [
                'modulo_codigo' => 'analytics',
                'codigo' => 'exportar_reportes',
                'nombre' => 'Exportar Reportes',
                'descripcion' => 'Permite exportar reportes a Excel/PDF',
                'tipo' => 'booleano',
                'valor_defecto' => 'false',
                'configurable_zona' => false,
                'configurable_restaurante' => true,
                'orden' => 3,
            ],
        ];

        // Obtener IDs de módulos
        $modulosIds = DB::table('modulos_sistema')->pluck('id', 'codigo');

        foreach ($funciones as $funcion) {
            $moduloCodigo = $funcion['modulo_codigo'];
            unset($funcion['modulo_codigo']);

            DB::table('funciones_sistema')->insert([
                'modulo_id' => $modulosIds[$moduloCodigo],
                ...$funcion,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
