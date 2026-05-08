<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorías del menú (Desayunos, Almuerzos, Bebidas, etc.)
        Schema::create('categorias_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->string('icono')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->time('disponible_desde')->nullable();
            $table->time('disponible_hasta')->nullable();
            $table->json('dias_disponibles')->nullable()->comment('Días de la semana');
            $table->timestamps();

            $table->unique(['restaurante_id', 'slug']);
        });

        // Platos/Productos
        Schema::create('platos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias_menu')->cascadeOnDelete();

            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->json('galeria')->nullable();

            // Precios
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_comparacion', 10, 2)->nullable()->comment('Precio anterior para mostrar descuento');
            $table->decimal('costo', 10, 2)->nullable()->comment('Costo para el restaurante');

            // Información nutricional
            $table->integer('calorias')->nullable();
            $table->json('info_nutricional')->nullable();
            $table->json('alergenos')->nullable()->comment('Maní, Gluten, Lácteos, etc.');
            $table->string('tamano_porcion')->nullable()->comment('Ej: 1 persona, 2-3 personas');

            // Configuración
            $table->integer('tiempo_preparacion')->nullable()->comment('Minutos');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->boolean('disponible')->default(true);
            $table->boolean('picante')->default(false);
            $table->boolean('vegetariano')->default(false);
            $table->boolean('vegano')->default(false);
            $table->boolean('sin_gluten')->default(false);

            // Estadísticas
            $table->integer('total_pedidos')->default(0);
            $table->decimal('calificacion', 3, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurante_id', 'slug']);
            $table->index(['activo', 'disponible']);
        });

        // Grupos de opciones/modificadores (Tamaño, Extras, etc.)
        Schema::create('grupos_opciones_plato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plato_id')->constrained('platos')->cascadeOnDelete();
            $table->string('nombre')->comment('Ej: Tamaño, Extras, Bebida');
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['unico', 'multiple'])->default('unico')->comment('Selección única o múltiple');
            $table->boolean('obligatorio')->default(false);
            $table->integer('minimo_selecciones')->default(0);
            $table->integer('maximo_selecciones')->default(1);
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Opciones individuales dentro de un grupo
        Schema::create('opciones_plato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_opcion_id')->constrained('grupos_opciones_plato')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio_adicional', 10, 2)->default(0)->comment('Precio adicional');
            $table->boolean('predeterminado')->default(false);
            $table->boolean('disponible')->default(true);
            $table->integer('calorias')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // Combos/Menús especiales
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_menu')->nullOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_comparacion', 10, 2)->nullable()->comment('Suma de precios individuales');
            $table->decimal('ahorro', 10, 2)->nullable()->comment('Cantidad ahorrada');
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->date('disponible_desde')->nullable();
            $table->date('disponible_hasta')->nullable();
            $table->json('dias_disponibles')->nullable()->comment('Días disponibles');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['restaurante_id', 'slug']);
        });

        // Platos incluidos en combo
        Schema::create('items_combo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('combos')->cascadeOnDelete();
            $table->foreignId('plato_id')->constrained('platos')->cascadeOnDelete();
            $table->integer('cantidad')->default(1);
            $table->boolean('obligatorio')->default(true);
            $table->boolean('sustituible')->default(false);
            $table->json('platos_sustitutos')->nullable()->comment('IDs de platos alternativos');
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_combo');
        Schema::dropIfExists('combos');
        Schema::dropIfExists('opciones_plato');
        Schema::dropIfExists('grupos_opciones_plato');
        Schema::dropIfExists('platos');
        Schema::dropIfExists('categorias_menu');
    }
};
