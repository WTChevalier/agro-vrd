<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LandingsConfigVerticalSeeder — Fase 2 (Vive RD).
 *
 * Seeder genérico que sirve a los 4 verticales (Servi/Inmo/Educ/Agro).
 * Detecta el vertical leyendo APP_NAME del .env, o vía argumento --vertical=X.
 *
 * Cada vertical tiene su propio set de copy (hero, seo, cta) adaptado a su nicho.
 */
class LandingsConfigVerticalSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('landings_config')) {
            $this->command->warn('Tabla landings_config no existe — corre migrations primero.');
            return;
        }

        // Detectar vertical desde APP_NAME, .env, o env var explícita
        $vertical = $this->detectarVertical();

        $configs = $this->getConfigParaVertical($vertical);

        if (empty($configs)) {
            $this->command->error("Vertical '{$vertical}' no reconocido. Esperados: servi, inmo, educ, agro");
            return;
        }

        $now = now();

        foreach ($configs as $fila) {
            $fila['is_translatable'] = $fila['is_translatable'] ?? true;
            $fila['activo'] = $fila['activo'] ?? true;
            $fila['created_at'] = $now;
            $fila['updated_at'] = $now;
            $fila['valor'] = json_encode($fila['valor']);

            DB::table('landings_config')->updateOrInsert(
                ['clave' => $fila['clave']],
                $fila
            );
        }

        $this->command->info(sprintf(
            "✅ Seeded %d filas en landings_config para vertical=%s",
            count($configs),
            $vertical
        ));
    }

    protected function detectarVertical(): string
    {
        // 1. Env var explícita
        if ($v = env('SEED_VERTICAL')) {
            return strtolower($v);
        }

        // 2. APP_URL hostname
        $url = config('app.url', '');
        if (preg_match('/(servi|inmo|educ|agro|estilo)\.vrd\.do/', $url, $m)) {
            return $m[1];
        }

        // 3. APP_NAME
        $name = strtolower(config('app.name', ''));
        foreach (['servi', 'inmo', 'educ', 'agro', 'estilo'] as $v) {
            if (str_contains($name, $v)) return $v;
        }

        return 'desconocido';
    }

    protected function getConfigParaVertical(string $vertical): array
    {
        $catalogo = [
            // ─── ServiRD ──────────────────────────────────────────────────────
            'servi' => [
                'marca' => 'ServiRD',
                'tagline' => 'Servicios profesionales',
                'hero_title' => 'Servicios dominicanos a tu alcance',
                'hero_subtitle' => 'Plomeros, electricistas, carpinteros y más profesionales verificados de la República Dominicana.',
                'meta_title' => 'ServiRD — Servicios profesionales dominicanos | Vive RD',
                'meta_desc' => 'ServiRD conecta clientes con los mejores profesionales de servicios en RD: plomería, electricidad, carpintería, jardinería y más. Forma parte de Vive RD.',
                'keywords' => 'servicios dominicanos, plomeros RD, electricistas Santo Domingo, profesionales República Dominicana',
                'cta_title' => '¿Eres un profesional de servicios?',
                'cta_subtitle' => 'Únete gratis a la red dominicana del paraguas Vive RD. Encuentra clientes locales y ofrece tus servicios con confianza.',
                'cat_section_subtitle' => 'Encuentra al profesional perfecto para tu hogar o negocio',
                'hero_image' => 'https://images.unsplash.com/photo-1581244277943-fe4a9c777189?w=800&h=800&fit=crop',
            ],

            // ─── InmoRD ───────────────────────────────────────────────────────
            'inmo' => [
                'marca' => 'InmoRD',
                'tagline' => 'Inmuebles dominicanos',
                'hero_title' => 'Encuentra tu hogar en República Dominicana',
                'hero_subtitle' => 'Apartamentos, casas, terrenos y propiedades comerciales en todo el país. El portal inmobiliario del paraguas Vive RD.',
                'meta_title' => 'InmoRD — Inmuebles dominicanos | Vive RD',
                'meta_desc' => 'InmoRD es el portal inmobiliario de la República Dominicana. Encuentra apartamentos, casas, terrenos y comercial. Forma parte del paraguas Vive RD.',
                'keywords' => 'inmuebles RD, apartamentos Santo Domingo, casas República Dominicana, propiedades Punta Cana',
                'cta_title' => '¿Tienes una propiedad para vender o alquilar?',
                'cta_subtitle' => 'Llega a compradores locales y turistas internacionales en 8 idiomas. Únete gratis al paraguas Vive RD.',
                'cat_section_subtitle' => 'Apartamentos, casas, terrenos — todo lo que buscas en RD',
                'hero_image' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&h=800&fit=crop',
            ],

            // ─── EducRD ───────────────────────────────────────────────────────
            'educ' => [
                'marca' => 'EducRD',
                'tagline' => 'Educación dominicana',
                'hero_title' => 'Educación de calidad en República Dominicana',
                'hero_subtitle' => 'Escuelas, universidades, institutos técnicos y cursos online. Encuentra el camino educativo perfecto en RD.',
                'meta_title' => 'EducRD — Educación dominicana | Vive RD',
                'meta_desc' => 'EducRD es el directorio educativo de la República Dominicana: escuelas, universidades, cursos técnicos y online. Forma parte del paraguas Vive RD.',
                'keywords' => 'educación RD, universidades Santo Domingo, escuelas República Dominicana, cursos online',
                'cta_title' => '¿Eres una institución educativa?',
                'cta_subtitle' => 'Llega a estudiantes locales e internacionales. Únete gratis al paraguas Vive RD y promueve tu oferta educativa en 8 idiomas.',
                'cat_section_subtitle' => 'Encuentra tu camino educativo: desde primaria hasta posgrado',
                'hero_image' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&h=800&fit=crop',
            ],

            // ─── AgroRD ───────────────────────────────────────────────────────
            'agro' => [
                'marca' => 'AgroRD',
                'tagline' => 'Agro dominicano',
                'hero_title' => 'Agro dominicano: de la tierra a tu mesa',
                'hero_subtitle' => 'Productores, productos frescos y mercados agrícolas de toda la República Dominicana. Conexión directa con la tierra dominicana.',
                'meta_title' => 'AgroRD — Agro dominicano: productores, productos y mercados | Vive RD',
                'meta_desc' => 'AgroRD conecta consumidores con productores y mercados agrícolas de la República Dominicana. Productos frescos, locales y de calidad. Forma parte de Vive RD.',
                'keywords' => 'agro RD, productores dominicanos, mercados agrícolas RD, productos frescos República Dominicana',
                'cta_title' => '¿Eres productor o emprendedor agrícola?',
                'cta_subtitle' => 'Lleva tus productos directamente a hogares dominicanos y mercados internacionales. Únete gratis al paraguas Vive RD.',
                'cat_section_subtitle' => 'Productos frescos directamente del campo dominicano',
                'hero_image' => 'https://images.unsplash.com/photo-1500076656116-558758c991c1?w=800&h=800&fit=crop',
            ],
        ];

        if (! isset($catalogo[$vertical])) {
            return [];
        }

        $cfg = $catalogo[$vertical];

        // Generar las 24 filas estándar adaptadas
        return [
            ['clave' => 'hero.title', 'valor' => $cfg['hero_title'], 'grupo' => 'hero', 'descripcion' => 'Título H1 hero', 'tipo_input' => 'text', 'orden' => 1],
            ['clave' => 'hero.subtitle', 'valor' => $cfg['hero_subtitle'], 'grupo' => 'hero', 'descripcion' => 'Subtítulo hero', 'tipo_input' => 'textarea', 'orden' => 2],
            ['clave' => 'hero.cta_primary_text', 'valor' => 'Explorar categorías', 'grupo' => 'hero', 'tipo_input' => 'text', 'orden' => 3],
            ['clave' => 'hero.cta_primary_url', 'valor' => '#categorias', 'grupo' => 'hero', 'is_translatable' => false, 'tipo_input' => 'text', 'orden' => 4],
            ['clave' => 'hero.cta_secondary_text', 'valor' => 'Soy un negocio · Únete', 'grupo' => 'hero', 'tipo_input' => 'text', 'orden' => 5],
            ['clave' => 'hero.cta_secondary_url', 'valor' => '/registro', 'grupo' => 'hero', 'is_translatable' => false, 'tipo_input' => 'text', 'orden' => 6],
            ['clave' => 'hero.image_url', 'valor' => $cfg['hero_image'], 'grupo' => 'hero', 'is_translatable' => false, 'tipo_input' => 'image_url', 'orden' => 7],

            ['clave' => 'seo.meta_title', 'valor' => $cfg['meta_title'], 'grupo' => 'seo', 'descripcion' => '<title> tag', 'tipo_input' => 'text', 'orden' => 1],
            ['clave' => 'seo.meta_description', 'valor' => $cfg['meta_desc'], 'grupo' => 'seo', 'tipo_input' => 'textarea', 'orden' => 2],
            ['clave' => 'seo.og_image', 'valor' => "/images/marca-{$vertical}/og.jpg", 'grupo' => 'seo', 'is_translatable' => false, 'tipo_input' => 'image_url', 'orden' => 3],
            ['clave' => 'seo.keywords', 'valor' => $cfg['keywords'], 'grupo' => 'seo', 'tipo_input' => 'textarea', 'orden' => 4],

            ['clave' => 'stats.show', 'valor' => ['categorias', 'negocios', 'ciudades'], 'grupo' => 'stats', 'is_translatable' => false, 'tipo_input' => 'array', 'orden' => 1],
            ['clave' => 'stats.categorias_label', 'valor' => 'Categorías activas', 'grupo' => 'stats', 'tipo_input' => 'text', 'orden' => 2],
            ['clave' => 'stats.negocios_label', 'valor' => 'Negocios registrados', 'grupo' => 'stats', 'tipo_input' => 'text', 'orden' => 3],
            ['clave' => 'stats.ciudades_label', 'valor' => 'Ciudades cubiertas', 'grupo' => 'stats', 'tipo_input' => 'text', 'orden' => 4],

            ['clave' => 'categorias.section_title', 'valor' => 'Explora por categoría', 'grupo' => 'categorias', 'tipo_input' => 'text', 'orden' => 1],
            ['clave' => 'categorias.section_subtitle', 'valor' => $cfg['cat_section_subtitle'], 'grupo' => 'categorias', 'tipo_input' => 'text', 'orden' => 2],

            ['clave' => 'cta.footer_title', 'valor' => $cfg['cta_title'], 'grupo' => 'cta', 'tipo_input' => 'text', 'orden' => 1],
            ['clave' => 'cta.footer_subtitle', 'valor' => $cfg['cta_subtitle'], 'grupo' => 'cta', 'tipo_input' => 'textarea', 'orden' => 2],
            ['clave' => 'cta.footer_button_text', 'valor' => 'Registrar mi negocio', 'grupo' => 'cta', 'tipo_input' => 'text', 'orden' => 3],
            ['clave' => 'cta.footer_button_url', 'valor' => '/registro', 'grupo' => 'cta', 'is_translatable' => false, 'tipo_input' => 'text', 'orden' => 4],

            ['clave' => 'paraguas.label', 'valor' => 'Eres parte de Vive RD · El paraguas digital de Marca País', 'grupo' => 'paraguas', 'tipo_input' => 'text', 'orden' => 1],

            ['clave' => 'footer.about_title', 'valor' => 'Sobre Vive RD', 'grupo' => 'footer', 'tipo_input' => 'text', 'orden' => 1],
            ['clave' => 'footer.about_text', 'valor' => 'Vive RD es el paraguas digital de la República Dominicana, conectando 6 verticales: turismo, belleza, servicios, inmuebles, educación y agro.', 'grupo' => 'footer', 'tipo_input' => 'textarea', 'orden' => 2],
            ['clave' => 'footer.copyright', 'valor' => '© Gurztac Productions Inc · Todos los derechos reservados', 'grupo' => 'footer', 'tipo_input' => 'text', 'orden' => 3],
        ];
    }
}

