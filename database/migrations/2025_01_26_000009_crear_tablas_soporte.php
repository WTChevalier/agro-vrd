<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Favoritos del usuario
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->morphs('favoritable'); // Puede ser restaurante o plato
            $table->timestamps();

            $table->unique(['usuario_id', 'favoritable_type', 'favoritable_id']);
        });

        // Direcciones guardadas
        Schema::create('direcciones_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();

            $table->string('etiqueta')->comment('Casa, Trabajo, etc.');
            $table->string('direccion_linea_1');
            $table->string('direccion_linea_2')->nullable();
            $table->string('referencia')->nullable()->comment('Cerca de...');
            $table->foreignId('provincia_id')->nullable()->constrained('provincias')->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectores')->nullOnDelete();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->text('instrucciones_entrega')->nullable();
            $table->boolean('predeterminada')->default(false);
            $table->boolean('verificada')->default(false);

            $table->timestamps();

            $table->index(['usuario_id', 'predeterminada']);
        });

        // Carrito de compras (persistente)
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->cascadeOnDelete();
            $table->string('id_sesion')->nullable();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('cupon_id')->nullable()->constrained('cupones')->nullOnDelete();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->timestamp('expira_en')->nullable();
            $table->timestamps();

            $table->index('id_sesion');
        });

        Schema::create('items_carrito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->foreignId('plato_id')->nullable()->constrained('platos')->cascadeOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained('combos')->cascadeOnDelete();
            $table->integer('cantidad');
            $table->json('opciones_seleccionadas')->nullable();
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_opciones', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->text('instrucciones_especiales')->nullable();
            $table->timestamps();
        });

        // Tickets de soporte
        Schema::create('tickets_soporte', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket')->unique();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('restaurante_id')->nullable()->constrained('restaurantes')->nullOnDelete();
            $table->foreignId('asignado_a')->nullable()->constrained('usuarios')->nullOnDelete();

            $table->string('asunto');
            $table->text('descripcion')->nullable();
            $table->enum('categoria', ['problema_pedido', 'pago', 'delivery', 'restaurante', 'tecnico', 'sugerencia', 'otro'])->default('otro');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->enum('estado', ['abierto', 'en_progreso', 'esperando_respuesta', 'resuelto', 'cerrado'])->default('abierto');
            $table->text('resolucion')->nullable();
            $table->tinyInteger('satisfaccion')->nullable()->comment('1-5');

            $table->timestamp('primera_respuesta_en')->nullable();
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'estado']);
        });

        Schema::create('mensajes_soporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets_soporte')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->text('mensaje');
            $table->json('adjuntos')->nullable();
            $table->boolean('es_interno')->default(false)->comment('Nota interna del staff');
            $table->boolean('es_automatico')->default(false);
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_en')->nullable();
            $table->timestamps();
        });

        // Configuración del sistema
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('grupo')->default('general');
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('tipo')->default('texto')->comment('texto, booleano, numero, json');
            $table->text('descripcion')->nullable();
            $table->boolean('es_publica')->default(false);
            $table->timestamps();

            $table->index('grupo');
        });

        // Banners/Sliders
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('subtitulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen');
            $table->string('imagen_movil')->nullable();

            // Enlace
            $table->enum('tipo_enlace', ['url', 'restaurante', 'promocion', 'categoria', 'ninguno'])->default('ninguno');
            $table->string('url_enlace')->nullable();
            $table->foreignId('restaurante_id')->nullable()->constrained('restaurantes')->nullOnDelete();
            $table->foreignId('promocion_id')->nullable()->constrained('promociones')->nullOnDelete();

            // Estilo
            $table->enum('posicion', ['hero_inicio', 'medio_inicio', 'categoria', 'restaurante'])->default('hero_inicio');
            $table->string('color_fondo', 20)->nullable();
            $table->string('color_texto', 20)->nullable();
            $table->string('texto_boton')->nullable();
            $table->string('color_boton', 20)->nullable();

            // Orden y estado
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamp('inicio_validez')->nullable();
            $table->timestamp('fin_validez')->nullable();

            // Estadísticas
            $table->integer('vistas')->default(0);
            $table->integer('clicks')->default(0);

            $table->timestamps();

            $table->index(['posicion', 'activo', 'orden']);
        });

        // Notificaciones
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo');
            $table->morphs('notificable');
            $table->text('datos');
            $table->timestamp('leido_en')->nullable();
            $table->timestamps();

            $table->index(['notificable_type', 'notificable_id', 'leido_en']);
        });

        // Tokens de notificaciones push
        Schema::create('tokens_push', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('token');
            $table->enum('plataforma', ['ios', 'android', 'web'])->default('web');
            $table->string('id_dispositivo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['usuario_id', 'token']);
        });

        // Configuración de notificaciones por usuario
        Schema::create('preferencias_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();

            // Email
            $table->boolean('email_estado_pedido')->default(true);
            $table->boolean('email_promociones')->default(true);
            $table->boolean('email_boletin')->default(false);

            // Push
            $table->boolean('push_estado_pedido')->default(true);
            $table->boolean('push_promociones')->default(true);
            $table->boolean('push_ofertas_cercanas')->default(false);

            // SMS
            $table->boolean('sms_estado_pedido')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferencias_notificaciones');
        Schema::dropIfExists('tokens_push');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('mensajes_soporte');
        Schema::dropIfExists('tickets_soporte');
        Schema::dropIfExists('items_carrito');
        Schema::dropIfExists('carritos');
        Schema::dropIfExists('direcciones_usuario');
        Schema::dropIfExists('favoritos');
    }
};
