<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla landing_blocks — Sprint 1067 (Fase 1 — Vive RD).
 *
 * Propósito: almacenar bloques de contenido más complejos que no caben en landings_config
 * por su naturaleza repetitiva (testimonials, FAQ, features, rich text, category highlights).
 *
 * Diferencia con landings_config:
 * - landings_config = strings sueltos (titles, descriptions, CTAs).
 * - landing_blocks = bloques estructurados con orden + activación + metadata.
 *
 * Cada bloque tiene un tipo (ENUM) que determina cómo se renderiza:
 * - testimonial: nombre, foto, ciudad, frase, rating.
 * - faq: pregunta + respuesta.
 * - feature: ícono + título + descripción.
 * - rich_text: contenido HTML libre (sanitizado).
 * - category_highlight: link a una categoría destacada con copy custom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_blocks', function (Blueprint $table) {
            $table->id();

            // Tipo de bloque — determina render frontend
            $table->enum('tipo', [
                'testimonial',
                'faq',
                'feature',
                'rich_text',
                'category_highlight',
                'cta_secundario',
                'partner_logo',
            ])->index();

            // Título corto del bloque (no necesariamente visible — útil para sortable/identificación en panel)
            $table->string('titulo', 255)->nullable();

            // Contenido estructurado del bloque — schema varía según tipo
            // Ejemplos:
            //   testimonial: {"nombre": "Ana", "ciudad": "Santo Domingo", "texto": "...", "rating": 5, "avatar": "/..."}
            //   faq:         {"pregunta": "...", "respuesta": "..."}
            //   feature:     {"icono": "fa-rocket", "titulo": "...", "descripcion": "..."}
            $table->json('contenido');

            // Orden de visualización (drag-to-sort en panel)
            $table->smallInteger('orden')->default(0)->index();

            // Activo: si false, no se muestra en frontend
            $table->boolean('activo')->default(true);

            // Metadatos: ej. fecha vigencia, autor, source url
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['tipo', 'activo']);
            $table->index(['tipo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_blocks');
    }
};
