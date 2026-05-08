<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perfiles de repartidores
        Schema::create('repartidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();

            // Documentos personales
            $table->string('cedula', 20)->nullable();
            $table->string('numero_licencia')->nullable();
            $table->date('fecha_vencimiento_licencia')->nullable();
            $table->string('numero_seguro')->nullable();
            $table->date('fecha_vencimiento_seguro')->nullable();

            // Vehículo
            $table->enum('tipo_vehiculo', ['motocicleta', 'bicicleta', 'carro', 'a_pie'])->default('motocicleta');
            $table->string('placa_vehiculo')->nullable();
            $table->string('marca_vehiculo')->nullable();
            $table->string('modelo_vehiculo')->nullable();
            $table->string('color_vehiculo')->nullable();
            $table->year('ano_vehiculo')->nullable();

            // Documentos subidos (imágenes)
            $table->string('imagen_cedula_frente')->nullable();
            $table->string('imagen_cedula_reverso')->nullable();
            $table->string('imagen_licencia')->nullable();
            $table->string('imagen_vehiculo')->nullable();
            $table->string('foto_perfil')->nullable();

            // Estado y verificación
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado', 'suspendido'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->boolean('activo')->default(false);
            $table->boolean('verificado')->default(false);
            $table->boolean('disponible')->default(true);
            $table->boolean('en_linea')->default(false);

            // Ubicación actual
            $table->decimal('latitud_actual', 10, 8)->nullable();
            $table->decimal('longitud_actual', 11, 8)->nullable();
            $table->timestamp('ubicacion_actualizada_en')->nullable();

            // Estadísticas
            $table->integer('total_entregas')->default(0);
            $table->decimal('calificacion', 3, 2)->default(0);
            $table->integer('total_calificaciones')->default(0);
            $table->decimal('ganancias_totales', 12, 2)->default(0);

            // Configuración
            $table->decimal('distancia_maxima_km', 5, 2)->default(10);
            $table->integer('pedidos_simultaneos_max')->default(2);
            $table->json('zonas_preferidas')->nullable();
            $table->json('horario_trabajo')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('en_linea');
            $table->index(['latitud_actual', 'longitud_actual']);
        });

        // Zonas de delivery generales de la plataforma
        Schema::create('zonas_delivery', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->json('poligono')->nullable()->comment('GeoJSON polygon');
            $table->decimal('tarifa_base', 10, 2)->default(0);
            $table->decimal('tarifa_por_km', 10, 2)->default(0);
            $table->integer('tiempo_estimado')->default(30);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Ganancias de repartidores por pedido
        Schema::create('ganancias_repartidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repartidor_id')->constrained('repartidores')->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->decimal('tarifa_delivery', 10, 2);
            $table->decimal('propina', 10, 2)->default(0);
            $table->decimal('bono', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('comision_plataforma', 10, 2)->default(0);
            $table->decimal('neto', 10, 2);
            $table->enum('estado', ['pendiente', 'pagado', 'cancelado'])->default('pendiente');
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();
        });

        // Pagos/Liquidaciones a repartidores
        Schema::create('pagos_repartidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repartidor_id')->constrained('repartidores')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago')->nullable()->comment('Transferencia, efectivo, etc.');
            $table->string('referencia')->nullable();
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])->default('pendiente');
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->integer('cantidad_entregas')->default(0);
            $table->text('notas')->nullable();
            $table->timestamp('procesado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_repartidor');
        Schema::dropIfExists('ganancias_repartidor');
        Schema::dropIfExists('zonas_delivery');
        Schema::dropIfExists('repartidores');
    }
};
