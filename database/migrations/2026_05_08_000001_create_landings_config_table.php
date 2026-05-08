<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla landings_config — Sprint 1067 (Fase 1 — Vive RD).
 *
 * Propósito: almacenar todo string CMS-driven de la landing pública del vertical.
 * Cada fila es un par clave→valor JSON, agrupado por sección (hero, seo, stats, cta, footer, etc).
 *
 * Patrón ecosistema:
 * - clave única tipo "hero.title", "hero.subtitle", "seo.meta_description".
 * - valor JSON permite estructuras: strings simples, arrays, objetos.
 * - is_translatable activa el trait HasTranslations en el modelo (replica patrón Sprint 599 de VRD).
 * - grupo facilita el filtrado en el panel Filament para tabs por sección.
 *
 * Replicable a: estilo_vrd, servi_vrd, inmo_vrd, educ_vrd, agro_vrd, visi_rd, vrd_paraguas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landings_config', function (Blueprint $table) {
            $table->id();

            // Clave única identificando el fragmento (ej: "hero.title", "seo.meta_description")
            $table->string('clave', 100)->unique();

            // Valor JSON — flexible para strings, arrays, objetos
            $table->json('valor');

            // Agrupación lógica para filtrado en panel
            $table->string('grupo', 50)->default('general')->index();

            // Si true, el contenido se traduce automáticamente vía trait HasTranslations
            $table->boolean('is_translatable')->default(true);

            // Metadatos editoriales para el panel
            $table->text('descripcion')->nullable()->comment('Para qué sirve esta clave (visible en panel)');

            // Hint UI para el panel: "text", "textarea", "rich_text", "image_url", "json", "array"
            $table->string('tipo_input', 20)->default('text')->comment('Hint para el form Filament');

            // Orden dentro del grupo en el panel
            $table->smallInteger('orden')->default(0);

            // Activo: si false, el helper get() devuelve fallback
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index(['grupo', 'orden']);
            $table->index(['activo', 'grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landings_config');
    }
};
