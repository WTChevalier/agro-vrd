<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cupones de descuento
        Schema::create('cupones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->nullable()->constrained('restaurantes')->cascadeOnDelete()->comment('Null = cupón global');

            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();

            $table->enum('tipo', ['porcentaje', 'monto_fijo', 'delivery_gratis', '2x1'])->default('porcentaje');
            $table->decimal('valor', 10, 2)->comment('Porcentaje o monto fijo');
            $table->decimal('descuento_maximo', 10, 2)->nullable();

            $table->decimal('pedido_minimo', 10, 2)->default(0);
            $table->integer('limite_uso')->nullable();
            $table->integer('limite_uso_por_usuario')->default(1);
            $table->integer('veces_usado')->default(0);

            $table->timestamp('inicio_validez')->nullable();
            $table->timestamp('fin_validez')->nullable();
            $table->json('dias_validos')->nullable();
            $table->time('hora_inicio_validez')->nullable();
            $table->time('hora_fin_validez')->nullable();

            $table->boolean('activo')->default(true);
            $table->boolean('publico')->default(false);
            $table->boolean('solo_primer_pedido')->default(false);
            $table->json('aplica_a')->nullable();
            $table->json('excluye')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['codigo', 'activo']);
        });

        // Promociones
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->nullable()->constrained('restaurantes')->cascadeOnDelete();

            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->string('imagen_banner')->nullable();

            $table->enum('tipo', ['descuento', 'delivery_gratis', '2x1', 'combo', 'flash_sale', 'happy_hour'])->default('descuento');
            $table->enum('tipo_descuento', ['porcentaje', 'monto_fijo'])->nullable();
            $table->decimal('valor_descuento', 10, 2)->nullable();
            $table->decimal('descuento_maximo', 10, 2)->nullable();
            $table->decimal('pedido_minimo', 10, 2)->default(0);
            $table->json('configuracion')->nullable();

            $table->json('platos_aplicables')->nullable();
            $table->json('categorias_aplicables')->nullable();
            $table->json('excluidos')->nullable();

            $table->timestamp('inicio_validez');
            $table->timestamp('fin_validez');
            $table->json('dias_disponibles')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            $table->integer('limite_uso')->nullable();
            $table->integer('veces_usado')->default(0);

            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->integer('orden')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['activo', 'inicio_validez', 'fin_validez']);
        });

        // Niveles de lealtad
        Schema::create('niveles_lealtad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->integer('puntos_requeridos');
            $table->decimal('multiplicador_puntos', 3, 2)->default(1);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);
            $table->boolean('delivery_gratis')->default(false);
            $table->json('beneficios')->nullable();
            $table->string('color_insignia', 20)->default('gris');
            $table->string('icono_insignia')->nullable();
            $table->string('imagen_insignia')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles_lealtad');
        Schema::dropIfExists('promociones');
        Schema::dropIfExists('cupones');
    }
};