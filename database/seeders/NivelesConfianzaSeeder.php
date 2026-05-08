<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Niveles de Confianza
 *
 * Define los niveles de confianza INTERNOS para restaurantes
 * Estos NO son visibles al público, solo al personal de SazónRD
 */
class NivelesConfianzaSeeder extends Seeder
{
    public function run(): void
    {
        $niveles = [
            // =====================================================================
            // NIVEL 1: NUEVO
            // Restaurante recién registrado, aún sin verificar
            // =====================================================================
            [
                'codigo' => 'nuevo',
                'nombre' => 'Nuevo',
                'descripcion' => 'Restaurante recién registrado. Requiere verificación inicial.',
                'color' => '#6B7280', // Gris
                'icono' => 'user-plus',
                'puntuacion_minima' => 0,
                'puntuacion_maxima' => 20,
                'beneficios' => [
                    'Acceso básico a la plataforma',
                    'Puede recibir visita de verificación',
                ],
                'restricciones' => [
                    'No puede recibir pedidos online',
                    'Pago adelantado obligatorio',
                    'No puede crear cupones',
                    'Verificación cada 30 días',
                ],
                'requiere_verificacion_frecuente' => true,
                'dias_verificacion' => 30,
                'puede_recibir_pedidos' => false,
                'puede_manejar_efectivo' => false,
                'limite_credito' => 0,
                'orden' => 1,
            ],

            // =====================================================================
            // NIVEL 2: EN OBSERVACIÓN
            // Verificado pero aún construyendo historial
            // =====================================================================
            [
                'codigo' => 'observacion',
                'nombre' => 'En Observación',
                'descripcion' => 'Restaurante verificado, construyendo historial de confianza.',
                'color' => '#F59E0B', // Amarillo
                'icono' => 'eye',
                'puntuacion_minima' => 21,
                'puntuacion_maxima' => 50,
                'beneficios' => [
                    'Puede participar en promociones de plataforma',
                    'Visibilidad normal en búsquedas',
                    'Acceso a estadísticas básicas',
                ],
                'restricciones' => [
                    'Pedidos online con restricciones',
                    'Crédito limitado (RD$ 5,000)',
                    'Verificación cada 60 días',
                    'Monitoreo de quejas activo',
                ],
                'requiere_verificacion_frecuente' => true,
                'dias_verificacion' => 60,
                'puede_recibir_pedidos' => true,
                'puede_manejar_efectivo' => false,
                'limite_credito' => 5000,
                'orden' => 2,
            ],

            // =====================================================================
            // NIVEL 3: CONFIABLE
            // Restaurante con buen historial
            // =====================================================================
            [
                'codigo' => 'confiable',
                'nombre' => 'Confiable',
                'descripcion' => 'Restaurante con historial positivo comprobado.',
                'color' => '#10B981', // Verde
                'icono' => 'shield-check',
                'puntuacion_minima' => 51,
                'puntuacion_maxima' => 80,
                'beneficios' => [
                    'Todas las funciones del plan',
                    'Prioridad en soporte',
                    'Crédito ampliado (RD$ 20,000)',
                    'Puede manejar efectivo de plataforma',
                    'Acceso a promociones especiales',
                ],
                'restricciones' => [
                    'Verificación cada 90 días',
                    'Reporte mensual de incidentes',
                ],
                'requiere_verificacion_frecuente' => false,
                'dias_verificacion' => 90,
                'puede_recibir_pedidos' => true,
                'puede_manejar_efectivo' => true,
                'limite_credito' => 20000,
                'orden' => 3,
            ],

            // =====================================================================
            // NIVEL 4: SOCIO VERIFICADO
            // Máximo nivel de confianza
            // =====================================================================
            [
                'codigo' => 'socio',
                'nombre' => 'Socio Verificado',
                'descripcion' => 'Restaurante de máxima confianza. Socio estratégico de SazónRD.',
                'color' => '#7C3AED', // Violeta/Púrpura
                'icono' => 'award',
                'puntuacion_minima' => 81,
                'puntuacion_maxima' => 100,
                'beneficios' => [
                    'Todas las funciones sin restricciones',
                    'Crédito extendido (RD$ 50,000+)',
                    'Destacado en página de inicio',
                    'Prioridad máxima en soporte',
                    'Acceso anticipado a nuevas funciones',
                    'Participación en decisiones de plataforma',
                    'Descuentos en planes',
                    'Eventos exclusivos',
                ],
                'restricciones' => [
                    'Verificación semestral',
                    'Mantener estándares de calidad',
                ],
                'requiere_verificacion_frecuente' => false,
                'dias_verificacion' => 180,
                'puede_recibir_pedidos' => true,
                'puede_manejar_efectivo' => true,
                'limite_credito' => 50000,
                'orden' => 4,
            ],

            // =====================================================================
            // NIVEL ESPECIAL: SUSPENDIDO
            // Restaurante con problemas graves
            // =====================================================================
            [
                'codigo' => 'suspendido',
                'nombre' => 'Suspendido',
                'descripcion' => 'Restaurante suspendido temporalmente por incumplimiento.',
                'color' => '#DC2626', // Rojo
                'icono' => 'ban',
                'puntuacion_minima' => -100,
                'puntuacion_maxima' => -1,
                'beneficios' => [],
                'restricciones' => [
                    'No visible en la plataforma',
                    'No puede recibir pedidos',
                    'No puede usar ninguna función',
                    'Requiere reunión con supervisión',
                    'Plan de mejora obligatorio',
                ],
                'requiere_verificacion_frecuente' => true,
                'dias_verificacion' => 15,
                'puede_recibir_pedidos' => false,
                'puede_manejar_efectivo' => false,
                'limite_credito' => 0,
                'orden' => 5,
            ],
        ];

        foreach ($niveles as $nivel) {
            $beneficios = $nivel['beneficios'];
            $restricciones = $nivel['restricciones'];

            unset($nivel['beneficios'], $nivel['restricciones']);

            DB::table('niveles_confianza')->insert([
                ...$nivel,
                'beneficios' => json_encode($beneficios),
                'restricciones' => json_encode($restricciones),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
