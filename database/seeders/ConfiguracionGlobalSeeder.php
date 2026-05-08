<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Configuración Global
 *
 * Define la configuración inicial del sistema SazónRD
 */
class ConfiguracionGlobalSeeder extends Seeder
{
    public function run(): void
    {
        $configuraciones = [
            // =====================================================================
            // INFORMACIÓN GENERAL
            // =====================================================================
            [
                'grupo' => 'general',
                'clave' => 'nombre_plataforma',
                'valor' => 'SazónRD',
                'tipo' => 'texto',
                'descripcion' => 'Nombre de la plataforma',
                'es_publico' => true,
            ],
            [
                'grupo' => 'general',
                'clave' => 'slogan',
                'valor' => 'El sabor de República Dominicana en tu puerta',
                'tipo' => 'texto',
                'descripcion' => 'Slogan de la plataforma',
                'es_publico' => true,
            ],
            [
                'grupo' => 'general',
                'clave' => 'email_contacto',
                'valor' => 'contacto@sazonrd.com',
                'tipo' => 'texto',
                'descripcion' => 'Email de contacto principal',
                'es_publico' => true,
            ],
            [
                'grupo' => 'general',
                'clave' => 'telefono_contacto',
                'valor' => '+1 809-555-0123',
                'tipo' => 'texto',
                'descripcion' => 'Teléfono de contacto',
                'es_publico' => true,
            ],
            [
                'grupo' => 'general',
                'clave' => 'whatsapp_soporte',
                'valor' => '+18095550123',
                'tipo' => 'texto',
                'descripcion' => 'Número de WhatsApp para soporte',
                'es_publico' => true,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE NEGOCIO
            // =====================================================================
            [
                'grupo' => 'negocio',
                'clave' => 'moneda',
                'valor' => 'DOP',
                'tipo' => 'texto',
                'descripcion' => 'Código de moneda ISO',
                'es_publico' => true,
            ],
            [
                'grupo' => 'negocio',
                'clave' => 'simbolo_moneda',
                'valor' => 'RD$',
                'tipo' => 'texto',
                'descripcion' => 'Símbolo de moneda para mostrar',
                'es_publico' => true,
            ],
            [
                'grupo' => 'negocio',
                'clave' => 'itbis',
                'valor' => '18',
                'tipo' => 'numero',
                'descripcion' => 'Porcentaje de ITBIS',
                'es_publico' => false,
            ],
            [
                'grupo' => 'negocio',
                'clave' => 'propina_sugerida_porcentaje',
                'valor' => '10',
                'tipo' => 'numero',
                'descripcion' => 'Porcentaje de propina sugerida',
                'es_publico' => true,
            ],
            [
                'grupo' => 'negocio',
                'clave' => 'comision_plataforma_defecto',
                'valor' => '15',
                'tipo' => 'numero',
                'descripcion' => 'Comisión por defecto de la plataforma (%)',
                'es_publico' => false,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE PEDIDOS (CUANDO SE ACTIVE)
            // =====================================================================
            [
                'grupo' => 'pedidos',
                'clave' => 'pedido_minimo_global',
                'valor' => '200',
                'tipo' => 'numero',
                'descripcion' => 'Pedido mínimo global en RD$',
                'es_publico' => true,
            ],
            [
                'grupo' => 'pedidos',
                'clave' => 'tiempo_cancelacion_minutos',
                'valor' => '5',
                'tipo' => 'numero',
                'descripcion' => 'Minutos para cancelar pedido sin penalización',
                'es_publico' => true,
            ],
            [
                'grupo' => 'pedidos',
                'clave' => 'tiempo_preparacion_defecto',
                'valor' => '30',
                'tipo' => 'numero',
                'descripcion' => 'Tiempo de preparación por defecto (minutos)',
                'es_publico' => true,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE DELIVERY (CUANDO SE ACTIVE)
            // =====================================================================
            [
                'grupo' => 'delivery',
                'clave' => 'tarifa_base',
                'valor' => '100',
                'tipo' => 'numero',
                'descripcion' => 'Tarifa base de delivery en RD$',
                'es_publico' => true,
            ],
            [
                'grupo' => 'delivery',
                'clave' => 'tarifa_por_km',
                'valor' => '25',
                'tipo' => 'numero',
                'descripcion' => 'Tarifa adicional por km',
                'es_publico' => true,
            ],
            [
                'grupo' => 'delivery',
                'clave' => 'radio_maximo_km',
                'valor' => '15',
                'tipo' => 'numero',
                'descripcion' => 'Radio máximo de delivery en km',
                'es_publico' => true,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE PAGOS (CUANDO SE ACTIVE)
            // =====================================================================
            [
                'grupo' => 'pagos',
                'clave' => 'metodos_activos',
                'valor' => json_encode(['efectivo']),
                'tipo' => 'json',
                'descripcion' => 'Métodos de pago activos',
                'es_publico' => true,
            ],
            [
                'grupo' => 'pagos',
                'clave' => 'pasarela_activa',
                'valor' => '',
                'tipo' => 'texto',
                'descripcion' => 'Pasarela de pago principal',
                'es_publico' => false,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE SUSCRIPCIONES
            // =====================================================================
            [
                'grupo' => 'suscripciones',
                'clave' => 'dias_gracia',
                'valor' => '7',
                'tipo' => 'numero',
                'descripcion' => 'Días de gracia antes de suspender',
                'es_publico' => false,
            ],
            [
                'grupo' => 'suscripciones',
                'clave' => 'dias_aviso_renovacion',
                'valor' => '7',
                'tipo' => 'numero',
                'descripcion' => 'Días de anticipación para avisar renovación',
                'es_publico' => false,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE CONFIANZA
            // =====================================================================
            [
                'grupo' => 'confianza',
                'clave' => 'puntos_por_mes_antiguedad',
                'valor' => '2',
                'tipo' => 'numero',
                'descripcion' => 'Puntos de confianza por mes de antigüedad',
                'es_publico' => false,
            ],
            [
                'grupo' => 'confianza',
                'clave' => 'puntos_por_pago_puntual',
                'valor' => '5',
                'tipo' => 'numero',
                'descripcion' => 'Puntos por cada pago puntual',
                'es_publico' => false,
            ],
            [
                'grupo' => 'confianza',
                'clave' => 'puntos_penalizacion_mora',
                'valor' => '-10',
                'tipo' => 'numero',
                'descripcion' => 'Penalización por mora',
                'es_publico' => false,
            ],
            [
                'grupo' => 'confianza',
                'clave' => 'puntos_penalizacion_queja',
                'valor' => '-5',
                'tipo' => 'numero',
                'descripcion' => 'Penalización por queja validada',
                'es_publico' => false,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE SEGURIDAD
            // =====================================================================
            [
                'grupo' => 'seguridad',
                'clave' => 'intentos_login_max',
                'valor' => '5',
                'tipo' => 'numero',
                'descripcion' => 'Intentos máximos de login antes de bloqueo',
                'es_publico' => false,
            ],
            [
                'grupo' => 'seguridad',
                'clave' => 'tiempo_bloqueo_minutos',
                'valor' => '30',
                'tipo' => 'numero',
                'descripcion' => 'Minutos de bloqueo por intentos fallidos',
                'es_publico' => false,
            ],
            [
                'grupo' => 'seguridad',
                'clave' => 'sesion_duracion_horas',
                'valor' => '24',
                'tipo' => 'numero',
                'descripcion' => 'Duración de sesión en horas',
                'es_publico' => false,
            ],
            [
                'grupo' => 'seguridad',
                'clave' => '2fa_obligatorio_admin',
                'valor' => 'true',
                'tipo' => 'booleano',
                'descripcion' => '2FA obligatorio para administradores',
                'es_publico' => false,
            ],
            [
                'grupo' => 'seguridad',
                'clave' => '2fa_obligatorio_finanzas',
                'valor' => 'true',
                'tipo' => 'booleano',
                'descripcion' => '2FA obligatorio para personal de finanzas',
                'es_publico' => false,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE NOTIFICACIONES
            // =====================================================================
            [
                'grupo' => 'notificaciones',
                'clave' => 'email_habilitado',
                'valor' => 'true',
                'tipo' => 'booleano',
                'descripcion' => 'Notificaciones por email activas',
                'es_publico' => false,
            ],
            [
                'grupo' => 'notificaciones',
                'clave' => 'whatsapp_habilitado',
                'valor' => 'false',
                'tipo' => 'booleano',
                'descripcion' => 'Notificaciones por WhatsApp activas',
                'es_publico' => false,
            ],
            [
                'grupo' => 'notificaciones',
                'clave' => 'sms_habilitado',
                'valor' => 'false',
                'tipo' => 'booleano',
                'descripcion' => 'Notificaciones por SMS activas',
                'es_publico' => false,
            ],

            // =====================================================================
            // CONFIGURACIÓN DE REDES SOCIALES
            // =====================================================================
            [
                'grupo' => 'redes',
                'clave' => 'facebook',
                'valor' => 'https://facebook.com/sazonrd',
                'tipo' => 'texto',
                'descripcion' => 'URL de Facebook',
                'es_publico' => true,
            ],
            [
                'grupo' => 'redes',
                'clave' => 'instagram',
                'valor' => 'https://instagram.com/sazonrd',
                'tipo' => 'texto',
                'descripcion' => 'URL de Instagram',
                'es_publico' => true,
            ],
            [
                'grupo' => 'redes',
                'clave' => 'twitter',
                'valor' => 'https://twitter.com/sazonrd',
                'tipo' => 'texto',
                'descripcion' => 'URL de Twitter/X',
                'es_publico' => true,
            ],

            // =====================================================================
            // INTEGRACIÓN VISITRD
            // =====================================================================
            [
                'grupo' => 'visitrd',
                'clave' => 'sincronizacion_activa',
                'valor' => 'true',
                'tipo' => 'booleano',
                'descripcion' => 'Sincronización con visitRD activa',
                'es_publico' => false,
            ],
            [
                'grupo' => 'visitrd',
                'clave' => 'sso_activo',
                'valor' => 'true',
                'tipo' => 'booleano',
                'descripcion' => 'SSO con visitRD activo',
                'es_publico' => false,
            ],
        ];

        foreach ($configuraciones as $config) {
            DB::table('configuracion_global')->insert([
                ...$config,
                'modificable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
