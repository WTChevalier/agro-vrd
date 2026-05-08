<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Certificaciones
 *
 * Define las certificaciones PÚBLICAS que los clientes pueden ver
 * A, B, C, D, E - Similar a calificaciones de salubridad
 */
class CertificacionesSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // CERTIFICACIONES
        // =====================================================================
        $certificaciones = [
            [
                'codigo' => 'A',
                'nombre' => 'Certificación A',
                'descripcion' => 'Excelencia en todos los criterios. El más alto estándar de SazónRD.',
                'descripcion_publica' => '⭐ Restaurante de Excelencia - Cumple los más altos estándares de calidad, higiene y servicio.',
                'color' => '#10B981', // Verde
                'icono' => 'award',
                'puntuacion_minima' => 90,
                'puntuacion_maxima' => 100,
                'vigencia_meses' => 12,
                'beneficios_publicos' => [
                    'Sello "Excelencia SazónRD" visible',
                    'Destacado en búsquedas',
                    'Etiqueta de recomendación especial',
                ],
                'beneficios_restaurante' => [
                    'Máxima visibilidad en plataforma',
                    'Prioridad en página de inicio',
                    'Descuento 20% en plan',
                    'Materiales premium gratuitos',
                    'Placa de certificación especial',
                ],
                'orden' => 1,
            ],
            [
                'codigo' => 'B',
                'nombre' => 'Certificación B',
                'descripcion' => 'Muy buen desempeño en la mayoría de criterios.',
                'descripcion_publica' => '✓ Restaurante Recomendado - Excelente calidad y servicio verificado.',
                'color' => '#3B82F6', // Azul
                'icono' => 'thumbs-up',
                'puntuacion_minima' => 75,
                'puntuacion_maxima' => 89,
                'vigencia_meses' => 12,
                'beneficios_publicos' => [
                    'Sello "Recomendado SazónRD"',
                    'Aparece en sección destacados',
                ],
                'beneficios_restaurante' => [
                    'Mayor visibilidad',
                    'Descuento 10% en plan',
                    'Materiales estándar incluidos',
                    'Placa de certificación',
                ],
                'orden' => 2,
            ],
            [
                'codigo' => 'C',
                'nombre' => 'Certificación C',
                'descripcion' => 'Cumple con los estándares básicos requeridos.',
                'descripcion_publica' => '○ Restaurante Verificado - Cumple con los estándares de la plataforma.',
                'color' => '#F59E0B', // Amarillo
                'icono' => 'check-circle',
                'puntuacion_minima' => 60,
                'puntuacion_maxima' => 74,
                'vigencia_meses' => 6,
                'beneficios_publicos' => [
                    'Sello "Verificado SazónRD"',
                ],
                'beneficios_restaurante' => [
                    'Visibilidad normal',
                    'Materiales básicos disponibles',
                    'Sticker de verificación',
                ],
                'orden' => 3,
            ],
            [
                'codigo' => 'D',
                'nombre' => 'Certificación D',
                'descripcion' => 'Áreas de mejora identificadas. Requiere seguimiento.',
                'descripcion_publica' => '△ En Proceso de Mejora - Trabajando para mejorar su servicio.',
                'color' => '#F97316', // Naranja
                'icono' => 'alert-triangle',
                'puntuacion_minima' => 40,
                'puntuacion_maxima' => 59,
                'vigencia_meses' => 3,
                'beneficios_publicos' => [],
                'beneficios_restaurante' => [
                    'Plan de mejora asistido',
                    'Asesoría gratuita',
                    'Evaluación de seguimiento',
                ],
                'orden' => 4,
            ],
            [
                'codigo' => 'E',
                'nombre' => 'Certificación E',
                'descripcion' => 'No cumple estándares mínimos. Acción correctiva requerida.',
                'descripcion_publica' => '⚠ Requiere Atención - El restaurante está en proceso de corrección.',
                'color' => '#DC2626', // Rojo
                'icono' => 'x-circle',
                'puntuacion_minima' => 0,
                'puntuacion_maxima' => 39,
                'vigencia_meses' => 1,
                'beneficios_publicos' => [],
                'beneficios_restaurante' => [
                    'Plan de acción obligatorio',
                    'Visitas de seguimiento frecuentes',
                ],
                'orden' => 5,
            ],
        ];

        foreach ($certificaciones as $cert) {
            $beneficiosPublicos = $cert['beneficios_publicos'];
            $beneficiosRestaurante = $cert['beneficios_restaurante'];

            unset($cert['beneficios_publicos'], $cert['beneficios_restaurante']);

            DB::table('certificaciones')->insert([
                ...$cert,
                'beneficios_publicos' => json_encode($beneficiosPublicos),
                'beneficios_restaurante' => json_encode($beneficiosRestaurante),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =====================================================================
        // CRITERIOS DE CERTIFICACIÓN
        // =====================================================================
        $criterios = [
            // --- CALIDAD DE COMIDA (30%) ---
            [
                'codigo' => 'calidad_comida',
                'nombre' => 'Calidad de Comida',
                'descripcion' => 'Evaluación de la calidad, frescura y presentación de los platos',
                'categoria' => 'calidad',
                'peso' => 30,
                'puntuacion_maxima' => 100,
                'metodo_evaluacion' => 'manual',
                'instrucciones' => 'Evaluar: frescura de ingredientes, sabor, presentación, temperatura adecuada, tamaño de porción.',
                'frecuencia_evaluacion' => 'trimestral',
            ],

            // --- TIEMPO DE PREPARACIÓN (15%) ---
            [
                'codigo' => 'tiempo_preparacion',
                'nombre' => 'Tiempo de Preparación',
                'descripcion' => 'Tiempo promedio desde pedido hasta listo',
                'categoria' => 'tiempo',
                'peso' => 15,
                'puntuacion_maxima' => 100,
                'metodo_evaluacion' => 'automatico',
                'formula' => 'Si tiempo_promedio <= tiempo_estimado: 100. Por cada 5 min extra: -10 puntos.',
                'frecuencia_evaluacion' => 'continua',
            ],

            // --- HIGIENE Y LIMPIEZA (25%) ---
            [
                'codigo' => 'higiene',
                'nombre' => 'Higiene y Limpieza',
                'descripcion' => 'Condiciones sanitarias del establecimiento',
                'categoria' => 'higiene',
                'peso' => 25,
                'puntuacion_maxima' => 100,
                'metodo_evaluacion' => 'manual',
                'instrucciones' => 'Evaluar: limpieza cocina, almacenamiento alimentos, higiene personal, manejo de residuos, control de plagas.',
                'frecuencia_evaluacion' => 'trimestral',
            ],

            // --- SERVICIO AL CLIENTE (15%) ---
            [
                'codigo' => 'servicio',
                'nombre' => 'Servicio al Cliente',
                'descripcion' => 'Calidad de atención y resolución de problemas',
                'categoria' => 'servicio',
                'peso' => 15,
                'puntuacion_maxima' => 100,
                'metodo_evaluacion' => 'mixto',
                'formula' => '(calificacion_promedio * 20) + (tasa_resolucion_quejas * 50) + (tiempo_respuesta_score * 30)',
                'frecuencia_evaluacion' => 'mensual',
            ],

            // --- INFORMACIÓN ACTUALIZADA (10%) ---
            [
                'codigo' => 'informacion',
                'nombre' => 'Información Actualizada',
                'descripcion' => 'Precisión y actualización de menú, precios y horarios',
                'categoria' => 'informacion',
                'peso' => 10,
                'puntuacion_maxima' => 100,
                'metodo_evaluacion' => 'automatico',
                'formula' => 'Basado en: última actualización menú, reportes de precios incorrectos, horarios actualizados.',
                'frecuencia_evaluacion' => 'continua',
            ],

            // --- CUMPLIMIENTO DE PEDIDOS (5%) ---
            [
                'codigo' => 'cumplimiento',
                'nombre' => 'Cumplimiento de Pedidos',
                'descripcion' => 'Porcentaje de pedidos completados correctamente',
                'categoria' => 'operaciones',
                'peso' => 5,
                'puntuacion_maxima' => 100,
                'metodo_evaluacion' => 'automatico',
                'formula' => '(pedidos_completados_correctos / total_pedidos) * 100',
                'frecuencia_evaluacion' => 'continua',
            ],
        ];

        foreach ($criterios as $criterio) {
            DB::table('criterios_certificacion')->insert([
                ...$criterio,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
