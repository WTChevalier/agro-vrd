<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder de Provincias de República Dominicana
 *
 * Las 32 provincias organizadas por región
 */
class ProvinciasSeeder extends Seeder
{
    public function run(): void
    {
        $provincias = [
            // =====================================================================
            // REGIÓN CIBAO NORTE
            // =====================================================================
            ['nombre' => 'Espaillat', 'codigo' => 'ESP', 'region' => 'Cibao Norte'],
            ['nombre' => 'Puerto Plata', 'codigo' => 'PP', 'region' => 'Cibao Norte'],
            ['nombre' => 'Santiago', 'codigo' => 'STI', 'region' => 'Cibao Norte'],

            // =====================================================================
            // REGIÓN CIBAO SUR
            // =====================================================================
            ['nombre' => 'La Vega', 'codigo' => 'LV', 'region' => 'Cibao Sur'],
            ['nombre' => 'Monseñor Nouel', 'codigo' => 'MN', 'region' => 'Cibao Sur'],
            ['nombre' => 'Sánchez Ramírez', 'codigo' => 'SR', 'region' => 'Cibao Sur'],

            // =====================================================================
            // REGIÓN CIBAO NORDESTE
            // =====================================================================
            ['nombre' => 'Duarte', 'codigo' => 'DUA', 'region' => 'Cibao Nordeste'],
            ['nombre' => 'Hermanas Mirabal', 'codigo' => 'HM', 'region' => 'Cibao Nordeste'],
            ['nombre' => 'María Trinidad Sánchez', 'codigo' => 'MTS', 'region' => 'Cibao Nordeste'],
            ['nombre' => 'Samaná', 'codigo' => 'SAM', 'region' => 'Cibao Nordeste'],

            // =====================================================================
            // REGIÓN CIBAO NOROESTE
            // =====================================================================
            ['nombre' => 'Dajabón', 'codigo' => 'DAJ', 'region' => 'Cibao Noroeste'],
            ['nombre' => 'Monte Cristi', 'codigo' => 'MC', 'region' => 'Cibao Noroeste'],
            ['nombre' => 'Santiago Rodríguez', 'codigo' => 'SRO', 'region' => 'Cibao Noroeste'],
            ['nombre' => 'Valverde', 'codigo' => 'VAL', 'region' => 'Cibao Noroeste'],

            // =====================================================================
            // REGIÓN VALDESIA
            // =====================================================================
            ['nombre' => 'Azua', 'codigo' => 'AZU', 'region' => 'Valdesia'],
            ['nombre' => 'Peravia', 'codigo' => 'PER', 'region' => 'Valdesia'],
            ['nombre' => 'San Cristóbal', 'codigo' => 'SC', 'region' => 'Valdesia'],
            ['nombre' => 'San José de Ocoa', 'codigo' => 'SJO', 'region' => 'Valdesia'],

            // =====================================================================
            // REGIÓN OZAMA (GRAN SANTO DOMINGO)
            // =====================================================================
            ['nombre' => 'Distrito Nacional', 'codigo' => 'DN', 'region' => 'Ozama'],
            ['nombre' => 'Santo Domingo', 'codigo' => 'SD', 'region' => 'Ozama'],

            // =====================================================================
            // REGIÓN HIGUAMO
            // =====================================================================
            ['nombre' => 'Monte Plata', 'codigo' => 'MP', 'region' => 'Higuamo'],
            ['nombre' => 'San Pedro de Macorís', 'codigo' => 'SPM', 'region' => 'Higuamo'],
            ['nombre' => 'Hato Mayor', 'codigo' => 'HMY', 'region' => 'Higuamo'],

            // =====================================================================
            // REGIÓN YUMA (ESTE)
            // =====================================================================
            ['nombre' => 'El Seibo', 'codigo' => 'SEI', 'region' => 'Yuma'],
            ['nombre' => 'La Altagracia', 'codigo' => 'LA', 'region' => 'Yuma'],
            ['nombre' => 'La Romana', 'codigo' => 'LR', 'region' => 'Yuma'],

            // =====================================================================
            // REGIÓN ENRIQUILLO (SUROESTE)
            // =====================================================================
            ['nombre' => 'Bahoruco', 'codigo' => 'BAH', 'region' => 'Enriquillo'],
            ['nombre' => 'Barahona', 'codigo' => 'BAR', 'region' => 'Enriquillo'],
            ['nombre' => 'Independencia', 'codigo' => 'IND', 'region' => 'Enriquillo'],
            ['nombre' => 'Pedernales', 'codigo' => 'PED', 'region' => 'Enriquillo'],

            // =====================================================================
            // REGIÓN EL VALLE (OESTE)
            // =====================================================================
            ['nombre' => 'Elías Piña', 'codigo' => 'EP', 'region' => 'El Valle'],
            ['nombre' => 'San Juan', 'codigo' => 'SJ', 'region' => 'El Valle'],
        ];

        $orden = 1;
        foreach ($provincias as $provincia) {
            DB::table('provincias')->insert([
                'nombre' => $provincia['nombre'],
                'slug' => Str::slug($provincia['nombre']),
                'codigo' => $provincia['codigo'],
                'region' => $provincia['region'],
                'activo' => true,
                'orden' => $orden++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear algunos municipios importantes
        $this->crearMunicipiosPrincipales();
    }

    /**
     * Crear municipios principales de las zonas más importantes
     */
    private function crearMunicipiosPrincipales(): void
    {
        $provinciasIds = DB::table('provincias')->pluck('id', 'nombre');

        $municipios = [
            // Santo Domingo (Gran Santo Domingo)
            'Distrito Nacional' => [
                ['nombre' => 'Santo Domingo de Guzmán', 'tiene_cobertura' => true],
            ],
            'Santo Domingo' => [
                ['nombre' => 'Santo Domingo Este', 'tiene_cobertura' => true],
                ['nombre' => 'Santo Domingo Norte', 'tiene_cobertura' => true],
                ['nombre' => 'Santo Domingo Oeste', 'tiene_cobertura' => true],
                ['nombre' => 'Boca Chica', 'tiene_cobertura' => true],
                ['nombre' => 'Los Alcarrizos', 'tiene_cobertura' => true],
                ['nombre' => 'Pedro Brand', 'tiene_cobertura' => false],
                ['nombre' => 'San Antonio de Guerra', 'tiene_cobertura' => false],
            ],

            // Santiago
            'Santiago' => [
                ['nombre' => 'Santiago de los Caballeros', 'tiene_cobertura' => true],
                ['nombre' => 'Bisonó', 'tiene_cobertura' => false],
                ['nombre' => 'Jánico', 'tiene_cobertura' => false],
                ['nombre' => 'Licey al Medio', 'tiene_cobertura' => true],
                ['nombre' => 'Puñal', 'tiene_cobertura' => false],
                ['nombre' => 'Sabana Iglesia', 'tiene_cobertura' => false],
                ['nombre' => 'San José de las Matas', 'tiene_cobertura' => false],
                ['nombre' => 'Tamboril', 'tiene_cobertura' => true],
                ['nombre' => 'Villa González', 'tiene_cobertura' => true],
            ],

            // La Altagracia (Punta Cana)
            'La Altagracia' => [
                ['nombre' => 'Higüey', 'tiene_cobertura' => true],
                ['nombre' => 'San Rafael del Yuma', 'tiene_cobertura' => false],
                ['nombre' => 'Bávaro', 'tiene_cobertura' => true], // Zona Punta Cana
            ],

            // Puerto Plata
            'Puerto Plata' => [
                ['nombre' => 'San Felipe de Puerto Plata', 'tiene_cobertura' => true],
                ['nombre' => 'Sosúa', 'tiene_cobertura' => true],
                ['nombre' => 'Cabarete', 'tiene_cobertura' => true],
                ['nombre' => 'Imbert', 'tiene_cobertura' => false],
                ['nombre' => 'Luperón', 'tiene_cobertura' => false],
            ],

            // La Romana
            'La Romana' => [
                ['nombre' => 'La Romana', 'tiene_cobertura' => true],
                ['nombre' => 'Guaymate', 'tiene_cobertura' => false],
                ['nombre' => 'Villa Hermosa', 'tiene_cobertura' => true],
            ],

            // San Pedro de Macorís
            'San Pedro de Macorís' => [
                ['nombre' => 'San Pedro de Macorís', 'tiene_cobertura' => true],
                ['nombre' => 'Consuelo', 'tiene_cobertura' => true],
                ['nombre' => 'Quisqueya', 'tiene_cobertura' => true],
                ['nombre' => 'Ramón Santana', 'tiene_cobertura' => false],
            ],

            // La Vega
            'La Vega' => [
                ['nombre' => 'La Concepción de La Vega', 'tiene_cobertura' => true],
                ['nombre' => 'Constanza', 'tiene_cobertura' => false],
                ['nombre' => 'Jarabacoa', 'tiene_cobertura' => true],
                ['nombre' => 'Jima Abajo', 'tiene_cobertura' => false],
            ],

            // Samaná
            'Samaná' => [
                ['nombre' => 'Santa Bárbara de Samaná', 'tiene_cobertura' => true],
                ['nombre' => 'Las Terrenas', 'tiene_cobertura' => true],
                ['nombre' => 'Sánchez', 'tiene_cobertura' => false],
            ],
        ];

        foreach ($municipios as $provinciaNombre => $listaMunicipios) {
            if (!isset($provinciasIds[$provinciaNombre])) {
                continue;
            }

            $provinciaId = $provinciasIds[$provinciaNombre];
            $orden = 1;

            foreach ($listaMunicipios as $municipio) {
                DB::table('municipios')->insert([
                    'provincia_id' => $provinciaId,
                    'nombre' => $municipio['nombre'],
                    'slug' => Str::slug($municipio['nombre']),
                    'tiene_cobertura_delivery' => $municipio['tiene_cobertura'],
                    'activo' => true,
                    'orden' => $orden++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
