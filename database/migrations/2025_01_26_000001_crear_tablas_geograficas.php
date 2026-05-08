<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Provincias de República Dominicana
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('codigo', 10)->nullable();
            $table->string('region')->nullable()->comment('Norte, Sur, Este, etc.');
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Municipios
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_id')->constrained('provincias')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->boolean('tiene_cobertura_delivery')->default(false);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['provincia_id', 'slug']);
        });

        // Sectores/Barrios
        Schema::create('sectores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->string('codigo_postal', 10)->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->json('poligono')->nullable()->comment('GeoJSON del área');
            $table->boolean('tiene_cobertura_delivery')->default(false);
            $table->decimal('ajuste_tarifa_delivery', 10, 2)->default(0);
            $table->integer('tiempo_estimado_delivery')->nullable()->comment('Minutos');
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['municipio_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectores');
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('provincias');
    }
};
