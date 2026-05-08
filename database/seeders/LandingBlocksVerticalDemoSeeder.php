<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LandingBlocksVerticalDemoSeeder — Fase 2 (Vive RD).
 *
 * Sembra 4 testimonials + 4 features adaptados al vertical detectado.
 */
class LandingBlocksVerticalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('landing_blocks')) {
            $this->command->warn('Tabla landing_blocks no existe.');
            return;
        }

        $vertical = $this->detectarVertical();
        $datos = $this->getDataParaVertical($vertical);

        if (empty($datos)) {
            $this->command->error("Vertical '{$vertical}' no reconocido");
            return;
        }

        $now = now();

        foreach ($datos['testimonials'] as $i => $t) {
            DB::table('landing_blocks')->updateOrInsert(
                ['tipo' => 'testimonial', 'orden' => $i + 1],
                [
                    'tipo' => 'testimonial',
                    'titulo' => "Testimonio {$t['nombre']}",
                    'contenido' => json_encode($t),
                    'orden' => $i + 1,
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        foreach ($datos['features'] as $i => $f) {
            DB::table('landing_blocks')->updateOrInsert(
                ['tipo' => 'feature', 'orden' => $i + 1],
                [
                    'tipo' => 'feature',
                    'titulo' => ($i + 1) . '. ' . $f['titulo'],
                    'contenido' => json_encode($f),
                    'orden' => $i + 1,
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info(sprintf(
            "✅ Seeded %d testimonials + %d features para vertical=%s",
            count($datos['testimonials']),
            count($datos['features']),
            $vertical
        ));
    }

    protected function detectarVertical(): string
    {
        if ($v = env('SEED_VERTICAL')) return strtolower($v);
        $url = config('app.url', '');
        if (preg_match('/(servi|inmo|educ|agro|estilo)\.vrd\.do/', $url, $m)) return $m[1];
        return 'desconocido';
    }

    protected function getDataParaVertical(string $vertical): array
    {
        $catalogo = [
            'servi' => [
                'testimonials' => [
                    ['nombre' => 'Roberto Méndez', 'ciudad' => 'Santo Domingo, RD', 'texto' => 'Encontré un electricista profesional el mismo día. ServiRD me salvó de una emergencia eléctrica en mi casa.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=33'],
                    ['nombre' => 'Laura Domínguez', 'ciudad' => 'Santiago, RD', 'texto' => 'Plomero, carpintero y jardinero — todo en una sola plataforma con precios transparentes. Recomendado.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=44'],
                    ['nombre' => 'Manuel Cabrera', 'ciudad' => 'La Romana, RD', 'texto' => 'Como dueño de un servicio de limpieza, ServiRD me trajo 12 nuevos clientes en mi primer mes.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=14'],
                    ['nombre' => 'Carmen Reyes', 'ciudad' => 'Punta Cana, RD', 'texto' => 'Como turista, necesitaba un técnico de aire acondicionado urgente. ServiRD me conectó en minutos.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=20'],
                ],
                'features' => [
                    ['icono' => 'fa-search', 'titulo' => 'Busca el servicio', 'descripcion' => 'Plomería, electricidad, carpintería, limpieza... cientos de servicios verificados a tu alcance.'],
                    ['icono' => 'fa-shield-halved', 'titulo' => 'Profesionales verificados', 'descripcion' => 'Cada profesional pasa por verificación de identidad y experiencia antes de aparecer en ServiRD.'],
                    ['icono' => 'fa-calendar-check', 'titulo' => 'Reserva o contacta', 'descripcion' => 'Habla directo con el profesional o reserva online cuando esté disponible. Sin intermediarios.'],
                    ['icono' => 'fa-star', 'titulo' => 'Califica + recomienda', 'descripcion' => 'Tu reseña ayuda a otros dominicanos a encontrar a los mejores profesionales del país.'],
                ],
            ],

            'inmo' => [
                'testimonials' => [
                    ['nombre' => 'Pedro Almonte', 'ciudad' => 'Santo Domingo, RD', 'texto' => 'Encontré mi apartamento en Bella Vista en 2 semanas. InmoRD tiene listings actualizados con fotos reales y precios transparentes.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=15'],
                    ['nombre' => 'Isabella Ramírez', 'ciudad' => 'Punta Cana, RD', 'texto' => 'Como inversionista internacional, necesitaba claridad en precios y procesos. InmoRD me dio confianza para invertir.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=25'],
                    ['nombre' => 'Felipe Castillo', 'ciudad' => 'Santiago, RD', 'texto' => 'Como agente, InmoRD me trae compradores serios. Plataforma profesional con buen flujo de leads.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=12'],
                    ['nombre' => 'Sandra Beltrán', 'ciudad' => 'Casa de Campo, RD', 'texto' => 'Vendí mi villa en menos de 3 meses gracias a InmoRD. El alcance internacional fue clave para encontrar al comprador adecuado.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=23'],
                ],
                'features' => [
                    ['icono' => 'fa-search-location', 'titulo' => 'Explora por zona', 'descripcion' => 'Apartamentos, casas, terrenos, comercial — todo el país filtrable por sector y precio.'],
                    ['icono' => 'fa-camera-retro', 'titulo' => 'Fotos y tours reales', 'descripcion' => 'Cada propiedad incluye fotos verificadas, ubicación exacta y detalles claros.'],
                    ['icono' => 'fa-comments', 'titulo' => 'Contacta al agente', 'descripcion' => 'Habla directo con el agente o dueño. Sin comisiones ocultas, sin intermediarios extra.'],
                    ['icono' => 'fa-handshake', 'titulo' => 'Cierra la operación', 'descripcion' => 'Soporte legal y guía para extranjeros disponible. Transparencia total en cada paso.'],
                ],
            ],

            'educ' => [
                'testimonials' => [
                    ['nombre' => 'Andrea Peña', 'ciudad' => 'Santo Domingo, RD', 'texto' => 'EducRD me ayudó a encontrar la universidad perfecta para mi hija. La información es completa y los rankings son transparentes.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=43'],
                    ['nombre' => 'Diego Hernández', 'ciudad' => 'Santiago, RD', 'texto' => 'Cursos técnicos certificados a precios accesibles. Mi nuevo trabajo lo encontré gracias al curso que tomé vía EducRD.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=37'],
                    ['nombre' => 'Patricia Vargas', 'ciudad' => 'La Vega, RD', 'texto' => 'Como directora de escuela, EducRD nos dio visibilidad nacional e internacional. Inscripciones aumentaron 40%.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=31'],
                    ['nombre' => 'Carlos Mejía', 'ciudad' => 'Punta Cana, RD', 'texto' => 'Buscaba clases de español para mis hijos expatriados. EducRD me conectó con tutores certificados en horario flexible.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=8'],
                ],
                'features' => [
                    ['icono' => 'fa-graduation-cap', 'titulo' => 'Explora por nivel', 'descripcion' => 'Primaria, secundaria, universidad, posgrado, técnico, idiomas, online — todos los niveles educativos en un solo lugar.'],
                    ['icono' => 'fa-medal', 'titulo' => 'Instituciones verificadas', 'descripcion' => 'Cada escuela y universidad pasa por verificación de acreditaciones y reputación.'],
                    ['icono' => 'fa-comments-dollar', 'titulo' => 'Compara precios y becas', 'descripcion' => 'Información clara de costos, becas disponibles y opciones de financiación.'],
                    ['icono' => 'fa-rocket', 'titulo' => 'Inscríbete con confianza', 'descripcion' => 'Solicita información, agenda visita o inscripción directamente con la institución. Cero intermediarios.'],
                ],
            ],

            'agro' => [
                'testimonials' => [
                    ['nombre' => 'Don Ramón Polanco', 'ciudad' => 'Constanza, RD', 'texto' => 'Como productor de fresas, AgroRD me conectó con restaurantes de Santo Domingo y Punta Cana. Mis ventas se duplicaron.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=11'],
                    ['nombre' => 'María Rosa Tejeda', 'ciudad' => 'Jarabacoa, RD', 'texto' => 'Compro café directamente a productores locales gracias a AgroRD. Calidad premium, precio justo, cero intermediarios.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=48'],
                    ['nombre' => 'Hipólito Mercedes', 'ciudad' => 'San Cristóbal, RD', 'texto' => 'Mi cooperativa de cacaoteros vende ahora a chocolaterías artesanales. AgroRD nos dio el alcance que necesitábamos.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=68'],
                    ['nombre' => 'Carolina Báez', 'ciudad' => 'Higüey, RD', 'texto' => 'Como chef, AgroRD me conecta con productores locales para tener ingredientes frescos diariamente. Sostenibilidad real.', 'rating' => 5, 'avatar' => 'https://i.pravatar.cc/150?img=29'],
                ],
                'features' => [
                    ['icono' => 'fa-seedling', 'titulo' => 'Productos frescos locales', 'descripcion' => 'Frutas, vegetales, café, cacao, ganadería — directamente del productor dominicano a tu mesa.'],
                    ['icono' => 'fa-tractor', 'titulo' => 'Productores verificados', 'descripcion' => 'Cada productor pasa por verificación de prácticas agrícolas y certificaciones (orgánico, comercio justo, etc).'],
                    ['icono' => 'fa-truck', 'titulo' => 'Logística transparente', 'descripcion' => 'Envíos directos a hogares, restaurantes y mercados. Información clara de zonas de cobertura y tiempos.'],
                    ['icono' => 'fa-leaf', 'titulo' => 'Apoya lo local', 'descripcion' => 'Cada compra fortalece a las familias agrícolas dominicanas y promueve la sostenibilidad del campo nacional.'],
                ],
            ],
        ];

        return $catalogo[$vertical] ?? [];
    }
}

