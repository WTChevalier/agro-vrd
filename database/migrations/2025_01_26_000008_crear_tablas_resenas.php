<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reseñas de restaurantes
        Schema::create('resenas_restaurante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->tinyInteger('calificacion_general');
            $table->tinyInteger('calificacion_comida')->nullable();
            $table->tinyInteger('calificacion_servicio')->nullable();
            $table->tinyInteger('calificacion_valor')->nullable();
            $table->text('comentario')->nullable();
            $table->json('imagenes')->nullable();
            $table->text('respuesta_restaurante')->nullable();
            $table->timestamp('respondido_en')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->boolean('destacado')->default(false);
            $table->integer('votos_util')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['pedido_id']);
            $table->index(['restaurante_id', 'estado', 'calificacion_general'], 'resenas_rest_idx');
        });

        // Reseñas de platos
        Schema::create('resenas_plato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plato_id')->constrained('platos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('item_pedido_id')->nullable()->constrained('items_pedido')->nullOnDelete();
            $table->tinyInteger('calificacion');
            $table->text('comentario')->nullable();
            $table->json('imagenes')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->integer('votos_util')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['plato_id', 'estado', 'calificacion'], 'resenas_plato_idx');
        });

        // Reseñas de repartidores
        Schema::create('resenas_repartidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repartidor_id')->constrained('repartidores')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->tinyInteger('calificacion');
            $table->text('comentario')->nullable();
            $table->boolean('fue_amable')->nullable();
            $table->boolean('llego_a_tiempo')->nullable();
            $table->boolean('comida_buen_estado')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('aprobado');
            $table->timestamps();
            $table->unique(['pedido_id']);
            $table->index(['repartidor_id', 'calificacion'], 'resenas_repart_idx');
        });

        // Votos de utilidad en reseñas
        Schema::create('votos_resena', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->morphs('resenable');
            $table->boolean('es_util');
            $table->timestamps();
            $table->unique(['usuario_id', 'resenable_type', 'resenable_id'], 'votos_resena_unique');
        });

        // Reportes de reseñas
        Schema::create('reportes_resena', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->morphs('resenable');
            $table->enum('motivo', ['spam', 'ofensivo', 'falso', 'irrelevante', 'otro']);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['pendiente', 'revisado', 'accion_tomada', 'desestimado'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_resena');
        Schema::dropIfExists('votos_resena');
        Schema::dropIfExists('resenas_repartidor');
        Schema::dropIfExists('resenas_plato');
        Schema::dropIfExists('resenas_restaurante');
    }
};