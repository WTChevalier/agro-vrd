<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MÓDULOS DEL SISTEMA
        Schema::create('modulos_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(false);
            $table->json('configuracion')->nullable();
            $table->integer('orden')->default(0);
            $table->string('icono', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->index('activo');
            $table->index('orden');
        });

        // FUNCIONES DEL SISTEMA
        Schema::create('funciones_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->foreignId('modulo_id')->nullable()->constrained('modulos_sistema')->nullOnDelete();
            $table->boolean('requiere_modulo_activo')->default(true);
            $table->string('tipo', 50)->default('funcionalidad');
            $table->json('opciones')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->index('modulo_id');
            $table->index('tipo');
        });

        // CONFIGURACIÓN POR ZONA
        Schema::create('configuracion_zonas', function (Blueprint $table) {
            $table->id();
            $table->string('zona_tipo', 50);
            $table->unsignedBigInteger('zona_id');
            $table->foreignId('modulo_id')->constrained('modulos_sistema')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->json('configuracion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->unique(['zona_tipo', 'zona_id', 'modulo_id'], 'config_zona_modulo_unique');
            $table->index(['zona_tipo', 'zona_id'], 'config_zona_idx');
        });

        // CONFIGURACIÓN GLOBAL
        Schema::create('configuracion_global', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('grupo', 50)->default('general');
            $table->text('valor')->nullable();
            $table->string('tipo', 20)->default('string');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->boolean('es_privado')->default(false);
            $table->boolean('editable')->default(true);
            $table->timestamps();
            $table->index('grupo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_global');
        Schema::dropIfExists('configuracion_zonas');
        Schema::dropIfExists('funciones_sistema');
        Schema::dropIfExists('modulos_sistema');
    }
};