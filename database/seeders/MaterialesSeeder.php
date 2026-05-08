<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder de Materiales Promocionales
 *
 * Define el inventario inicial de materiales disponibles
 */
class MaterialesSeeder extends Seeder
{
    public function run(): void
    {
        $materiales = [
            // =====================================================================
            // STICKERS
            // =====================================================================
            [
                'codigo' => 'sticker_puerta_15x15',
                'nombre' => 'Sticker de Puerta SazónRD',
                'descripcion' => 'Sticker para puerta de entrada con logo SazónRD y código QR único del restaurante.',
                'tipo' => 'sticker',
                'tamano' => '15x15cm',
                'material_fabricacion' => 'Vinil adhesivo resistente UV',
                'costo_unitario' => 50,
                'precio_venta' => null,
                'stock_minimo' => 50,
                'tiempo_produccion_dias' => 3,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'sticker_ventana_20x20',
                'nombre' => 'Sticker de Ventana Grande',
                'descripcion' => 'Sticker grande para ventana con logo y QR.',
                'tipo' => 'sticker',
                'tamano' => '20x20cm',
                'material_fabricacion' => 'Vinil transparente',
                'costo_unitario' => 75,
                'precio_venta' => null,
                'stock_minimo' => 30,
                'tiempo_produccion_dias' => 3,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'sticker_delivery_moto',
                'nombre' => 'Sticker para Moto/Caja Delivery',
                'descripcion' => 'Sticker resistente para cajas de delivery.',
                'tipo' => 'sticker',
                'tamano' => '25x10cm',
                'material_fabricacion' => 'Vinil laminado resistente agua',
                'costo_unitario' => 60,
                'precio_venta' => 100,
                'stock_minimo' => 100,
                'tiempo_produccion_dias' => 3,
                'requiere_personalizacion' => false,
                'requiere_certificacion' => false,
            ],

            // =====================================================================
            // DISPLAYS DE MESA
            // =====================================================================
            [
                'codigo' => 'display_mesa_qr',
                'nombre' => 'Display de Mesa con QR',
                'descripcion' => 'Display triangular para mesas con código QR para escanear.',
                'tipo' => 'display',
                'tamano' => '10x10x15cm',
                'material_fabricacion' => 'Acrílico 3mm',
                'costo_unitario' => 120,
                'precio_venta' => 200,
                'stock_minimo' => 50,
                'tiempo_produccion_dias' => 7,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'display_mostrador',
                'nombre' => 'Display de Mostrador',
                'descripcion' => 'Display para mostrador con información de SazónRD.',
                'tipo' => 'display',
                'tamano' => '20x25cm',
                'material_fabricacion' => 'Acrílico 5mm con base',
                'costo_unitario' => 200,
                'precio_venta' => 350,
                'stock_minimo' => 20,
                'tiempo_produccion_dias' => 10,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],

            // =====================================================================
            // PLACAS DE CERTIFICACIÓN
            // =====================================================================
            [
                'codigo' => 'placa_cert_a',
                'nombre' => 'Placa Certificación A',
                'descripcion' => 'Placa dorada de certificación A - Excelencia.',
                'tipo' => 'placa',
                'tamano' => '20x15cm',
                'material_fabricacion' => 'Acrílico dorado grabado láser',
                'costo_unitario' => 500,
                'precio_venta' => null,
                'stock_minimo' => 10,
                'tiempo_produccion_dias' => 14,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => true,
                'certificacion_minima' => 'A',
            ],
            [
                'codigo' => 'placa_cert_b',
                'nombre' => 'Placa Certificación B',
                'descripcion' => 'Placa plateada de certificación B - Recomendado.',
                'tipo' => 'placa',
                'tamano' => '20x15cm',
                'material_fabricacion' => 'Acrílico plateado grabado láser',
                'costo_unitario' => 400,
                'precio_venta' => null,
                'stock_minimo' => 15,
                'tiempo_produccion_dias' => 14,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => true,
                'certificacion_minima' => 'B',
            ],
            [
                'codigo' => 'placa_cert_c',
                'nombre' => 'Placa Certificación C',
                'descripcion' => 'Placa de certificación C - Verificado.',
                'tipo' => 'placa',
                'tamano' => '15x12cm',
                'material_fabricacion' => 'Acrílico blanco grabado',
                'costo_unitario' => 250,
                'precio_venta' => null,
                'stock_minimo' => 20,
                'tiempo_produccion_dias' => 10,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => true,
                'certificacion_minima' => 'C',
            ],

            // =====================================================================
            // FLYERS Y MATERIAL IMPRESO
            // =====================================================================
            [
                'codigo' => 'flyer_menu_a5',
                'nombre' => 'Flyer Menú A5',
                'descripcion' => 'Flyer tamaño A5 con menú destacado del restaurante.',
                'tipo' => 'flyer',
                'tamano' => 'A5 (14.8x21cm)',
                'material_fabricacion' => 'Papel couché 150g',
                'costo_unitario' => 5,
                'precio_venta' => 10,
                'stock_minimo' => 500,
                'tiempo_produccion_dias' => 5,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'tarjeta_presentacion',
                'nombre' => 'Tarjetas de Presentación',
                'descripcion' => 'Pack de 100 tarjetas con QR del restaurante.',
                'tipo' => 'flyer',
                'tamano' => '9x5cm',
                'material_fabricacion' => 'Cartulina 350g laminado mate',
                'costo_unitario' => 150, // Por paquete de 100
                'precio_venta' => 250,
                'stock_minimo' => 20, // Paquetes
                'tiempo_produccion_dias' => 5,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],

            // =====================================================================
            // UNIFORMES Y ACCESORIOS
            // =====================================================================
            [
                'codigo' => 'gorra_sazonrd',
                'nombre' => 'Gorra SazónRD',
                'descripcion' => 'Gorra bordada con logo SazónRD para repartidores.',
                'tipo' => 'uniforme',
                'tamano' => 'Unitalla ajustable',
                'material_fabricacion' => 'Algodón/Poliéster',
                'costo_unitario' => 180,
                'precio_venta' => 300,
                'stock_minimo' => 30,
                'tiempo_produccion_dias' => 14,
                'requiere_personalizacion' => false,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'camiseta_sazonrd',
                'nombre' => 'Camiseta SazónRD',
                'descripcion' => 'Camiseta con logo SazónRD para personal.',
                'tipo' => 'uniforme',
                'tamano' => 'S, M, L, XL, XXL',
                'material_fabricacion' => 'Algodón 100%',
                'costo_unitario' => 250,
                'precio_venta' => 400,
                'stock_minimo' => 50,
                'tiempo_produccion_dias' => 14,
                'requiere_personalizacion' => false,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'delantal_cocina',
                'nombre' => 'Delantal de Cocina',
                'descripcion' => 'Delantal con logo bordado.',
                'tipo' => 'uniforme',
                'tamano' => 'Unitalla',
                'material_fabricacion' => 'Tela resistente con bolsillos',
                'costo_unitario' => 200,
                'precio_venta' => 350,
                'stock_minimo' => 20,
                'tiempo_produccion_dias' => 14,
                'requiere_personalizacion' => false,
                'requiere_certificacion' => false,
            ],

            // =====================================================================
            // OTROS
            // =====================================================================
            [
                'codigo' => 'banner_roll_up',
                'nombre' => 'Banner Roll-Up',
                'descripcion' => 'Banner retráctil para eventos y promociones.',
                'tipo' => 'otro',
                'tamano' => '85x200cm',
                'material_fabricacion' => 'Lona con estructura metálica',
                'costo_unitario' => 800,
                'precio_venta' => 1500,
                'stock_minimo' => 5,
                'tiempo_produccion_dias' => 7,
                'requiere_personalizacion' => true,
                'requiere_certificacion' => false,
            ],
            [
                'codigo' => 'mantel_individual',
                'nombre' => 'Mantel Individual',
                'descripcion' => 'Mantel individual de papel con diseño SazónRD.',
                'tipo' => 'otro',
                'tamano' => '30x40cm',
                'material_fabricacion' => 'Papel bond 90g',
                'costo_unitario' => 2, // Por unidad
                'precio_venta' => 5,
                'stock_minimo' => 1000,
                'tiempo_produccion_dias' => 5,
                'requiere_personalizacion' => false,
                'requiere_certificacion' => false,
            ],
        ];

        // Obtener IDs de certificaciones si existen
        $certificacionesIds = DB::table('certificaciones')->pluck('id', 'codigo');

        foreach ($materiales as $material) {
            $certMinima = $material['certificacion_minima'] ?? null;
            unset($material['certificacion_minima']);

            DB::table('materiales_promocionales')->insert([
                ...$material,
                'certificacion_minima_id' => $certMinima ? ($certificacionesIds[$certMinima] ?? null) : null,
                'stock_actual' => $material['stock_minimo'] * 2, // Stock inicial
                'activo' => true,
                'orden' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // =====================================================================
        // KITS POR PLAN
        // =====================================================================
        $this->crearKitsPorPlan();
    }

    /**
     * Crear kits de materiales por plan
     */
    private function crearKitsPorPlan(): void
    {
        $planes = DB::table('planes')->pluck('id', 'codigo');
        $materiales = DB::table('materiales_promocionales')->pluck('id', 'codigo');

        $kits = [
            // Kit Básico
            'basico' => [
                ['material' => 'sticker_puerta_15x15', 'cantidad' => 1],
                ['material' => 'tarjeta_presentacion', 'cantidad' => 1], // 100 tarjetas
            ],

            // Kit Profesional
            'profesional' => [
                ['material' => 'sticker_puerta_15x15', 'cantidad' => 2],
                ['material' => 'sticker_ventana_20x20', 'cantidad' => 1],
                ['material' => 'display_mesa_qr', 'cantidad' => 5],
                ['material' => 'display_mostrador', 'cantidad' => 1],
                ['material' => 'tarjeta_presentacion', 'cantidad' => 2],
                ['material' => 'flyer_menu_a5', 'cantidad' => 100],
            ],

            // Kit Premium
            'premium' => [
                ['material' => 'sticker_puerta_15x15', 'cantidad' => 3],
                ['material' => 'sticker_ventana_20x20', 'cantidad' => 2],
                ['material' => 'display_mesa_qr', 'cantidad' => 10],
                ['material' => 'display_mostrador', 'cantidad' => 2],
                ['material' => 'tarjeta_presentacion', 'cantidad' => 5],
                ['material' => 'flyer_menu_a5', 'cantidad' => 500],
                ['material' => 'gorra_sazonrd', 'cantidad' => 2],
                ['material' => 'camiseta_sazonrd', 'cantidad' => 2],
                ['material' => 'delantal_cocina', 'cantidad' => 2],
                ['material' => 'banner_roll_up', 'cantidad' => 1],
            ],

            // Kit Cadena (personalizado por negociación)
            'cadena' => [
                ['material' => 'sticker_puerta_15x15', 'cantidad' => 5],
                ['material' => 'sticker_ventana_20x20', 'cantidad' => 5],
                ['material' => 'display_mesa_qr', 'cantidad' => 20],
                ['material' => 'display_mostrador', 'cantidad' => 5],
                ['material' => 'tarjeta_presentacion', 'cantidad' => 10],
                ['material' => 'flyer_menu_a5', 'cantidad' => 1000],
                ['material' => 'gorra_sazonrd', 'cantidad' => 10],
                ['material' => 'camiseta_sazonrd', 'cantidad' => 10],
                ['material' => 'delantal_cocina', 'cantidad' => 10],
                ['material' => 'banner_roll_up', 'cantidad' => 3],
            ],
        ];

        foreach ($kits as $planCodigo => $items) {
            if (!isset($planes[$planCodigo])) {
                continue;
            }

            foreach ($items as $item) {
                if (!isset($materiales[$item['material']])) {
                    continue;
                }

                DB::table('kits_plan')->insert([
                    'plan_id' => $planes[$planCodigo],
                    'material_id' => $materiales[$item['material']],
                    'cantidad' => $item['cantidad'],
                    'es_inicial' => true,
                    'es_renovable' => true,
                    'renovacion_cada_meses' => 12,
                    'maximo_renovaciones' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
