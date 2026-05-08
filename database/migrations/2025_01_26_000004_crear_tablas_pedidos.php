<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estados de pedido
        Schema::create('estados_pedido', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('color', 20)->default('gris');
            $table->string('icono')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->boolean('es_final')->default(false)->comment('Si es estado final (entregado, cancelado)');
            $table->boolean('notificar_cliente')->default(true);
            $table->boolean('notificar_restaurante')->default(true);
            $table->boolean('notificar_repartidor')->default(false);
            $table->timestamps();
        });

        // Pedidos
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido')->unique();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('estado_id')->constrained('estados_pedido');
            $table->foreignId('repartidor_id')->nullable()->constrained('usuarios')->nullOnDelete();

            // Tipo de pedido
            $table->enum('tipo', ['delivery', 'recoger', 'en_local'])->default('delivery');

            // Dirección de entrega
            $table->json('direccion_entrega')->nullable();
            $table->decimal('latitud_entrega', 10, 8)->nullable();
            $table->decimal('longitud_entrega', 11, 8)->nullable();
            $table->text('instrucciones_entrega')->nullable();

            // Montos
            $table->decimal('subtotal', 10, 2);
            $table->decimal('itbis', 10, 2)->default(0)->comment('ITBIS 18%');
            $table->decimal('tarifa_delivery', 10, 2)->default(0);
            $table->decimal('tarifa_servicio', 10, 2)->default(0);
            $table->decimal('propina', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Pago
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'billetera', 'transferencia'])->default('efectivo');
            $table->enum('estado_pago', ['pendiente', 'pagado', 'fallido', 'reembolsado'])->default('pendiente');
            $table->string('referencia_pago')->nullable();
            $table->timestamp('pagado_en')->nullable();

            // Cupón
            $table->foreignId('cupon_id')->nullable()->constrained('cupones')->nullOnDelete();
            $table->string('codigo_cupon')->nullable();

            // Tiempos
            $table->integer('tiempo_preparacion_estimado')->nullable()->comment('Minutos');
            $table->integer('tiempo_entrega_estimado')->nullable()->comment('Minutos');
            $table->timestamp('programado_para')->nullable()->comment('Pedido programado');
            $table->timestamp('confirmado_en')->nullable();
            $table->timestamp('preparando_en')->nullable();
            $table->timestamp('listo_en')->nullable();
            $table->timestamp('recogido_en')->nullable();
            $table->timestamp('entregado_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();

            // Notas
            $table->text('notas_cliente')->nullable();
            $table->text('notas_restaurante')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->enum('cancelado_por', ['cliente', 'restaurante', 'sistema', 'admin'])->nullable();

            // Calificación
            $table->tinyInteger('calificacion')->nullable();
            $table->text('resena')->nullable();
            $table->timestamp('resenado_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['usuario_id', 'estado_id']);
            $table->index(['restaurante_id', 'estado_id']);
            $table->index('created_at');
        });

        // Items del pedido
        Schema::create('items_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('plato_id')->nullable()->constrained('platos')->nullOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained('combos')->nullOnDelete();

            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->decimal('precio_unitario', 10, 2);
            $table->integer('cantidad');
            $table->decimal('subtotal', 10, 2);
            $table->text('instrucciones_especiales')->nullable();

            // Snapshot del producto al momento del pedido
            $table->json('opciones_seleccionadas')->nullable();
            $table->decimal('precio_opciones', 10, 2)->default(0);
            $table->boolean('es_combo')->default(false);

            $table->timestamps();
        });

        // Historial de estados del pedido
        Schema::create('historial_estados_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('estado_id')->constrained('estados_pedido');
            $table->foreignId('cambiado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->enum('tipo_cambio', ['cliente', 'restaurante', 'repartidor', 'sistema', 'admin'])->nullable();
            $table->text('notas')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Tracking de delivery en tiempo real
        Schema::create('tracking_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('repartidor_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->decimal('precision', 6, 2)->nullable()->comment('Precisión GPS en metros');
            $table->decimal('velocidad', 5, 2)->nullable()->comment('km/h');
            $table->decimal('direccion', 5, 2)->nullable()->comment('Dirección en grados');
            $table->enum('tipo_evento', ['ubicacion', 'llegada_restaurante', 'recogido', 'en_camino', 'cerca', 'entregado'])->default('ubicacion');
            $table->text('notas')->nullable();
            $table->timestamp('registrado_en');
            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_pedido');
        Schema::dropIfExists('historial_estados_pedido');
        Schema::dropIfExists('items_pedido');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('estados_pedido');
    }
};
