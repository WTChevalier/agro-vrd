<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregar columnas SEO/UI a categorías — Sprint 1067 (Fase 1 — Vive RD).
 *
 * La tabla `categorias` ya existe (Sprint 908 sembró 12 categorías por vertical).
 * Esta migración agrega columnas necesarias para:
 * 1. SEO específico por categoría (slug, meta_description, og_image).
 * 2. UI rica (icono FontAwesome, color hex, orden de visualización).
 * 3. i18n vía trait HasTranslations (los strings se mueven a traducciones_contenido en una pasada posterior).
 *
 * IMPORTANTE: Esta migración es ADITIVA — no toca columnas existentes.
 * Si algún campo ya existe, se hace skip con Schema::hasColumn().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (! Schema::hasColumn('categorias', 'slug')) {
                $table->string('slug', 120)->nullable()->after('nombre')->index();
            }

            if (! Schema::hasColumn('categorias', 'meta_description')) {
                $table->string('meta_description', 320)->nullable()->after('descripcion');
            }

            if (! Schema::hasColumn('categorias', 'icono')) {
                $table->string('icono', 60)->nullable()->after('meta_description')->comment('Clase FontAwesome o emoji');
            }

            if (! Schema::hasColumn('categorias', 'color_hex')) {
                $table->string('color_hex', 7)->nullable()->after('icono')->comment('#RRGGBB para tinte de la card');
            }

            if (! Schema::hasColumn('categorias', 'og_image')) {
                $table->string('og_image', 255)->nullable()->after('color_hex');
            }

            if (! Schema::hasColumn('categorias', 'orden')) {
                $table->smallInteger('orden')->default(0)->after('og_image')->index();
            }

            if (! Schema::hasColumn('categorias', 'destacada')) {
                $table->boolean('destacada')->default(false)->after('orden')->index();
            }

            if (! Schema::hasColumn('categorias', 'activa')) {
                $table->boolean('activa')->default(true)->after('destacada')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $columnasABorrar = [
                'slug',
                'meta_description',
                'icono',
                'color_hex',
                'og_image',
                'orden',
                'destacada',
                'activa',
            ];

            foreach ($columnasABorrar as $col) {
                if (Schema::hasColumn('categorias', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
