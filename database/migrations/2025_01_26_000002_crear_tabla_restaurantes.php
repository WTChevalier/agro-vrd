<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitrd_restaurante_id')->nullable()->unique()->comment('ID sincronizado desde visitRD');
            $table->foreignId('propietario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('provincia_id')->nullable()->constrained('provincias')->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectores')->nullOnDelete();

            $table->string('nombre');
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->string('descripcion_corta')->nullable();

            // Contacto
            $table->string('telefono', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('sitio_web')->nullable();

            // Ubicación
            $table->string('direccion')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();

            // Imágenes
            $table->string('logo')->nullable();
            $table->string('imagen_portada')->nullable();
            $table->json('galeria')->nullable();

            // Configuración
            $table->json('horarios_atencion')->nullable()->comment('Horario por día de la semana');
            $table->json('tipos_cocina')->nullable()->comment('Dominicana, Italiana, etc.');
            $table->decimal('pedido_minimo', 10, 2)->default(0);
            $table->integer('tiempo_preparacion')->default(30)->comment('Tiempo promedio en minutos');
            $table->decimal('tarifa_delivery', 10, 2)->default(0);
            $table->decimal('porcentaje_comision', 5, 2)->default(15)->comment('Comisión de la plataforma %');

            // Estado
            $table->boolean('activo')->default(true);
            $table->boolean('destacado')->default(false);
            $table->boolean('abierto')->default(true);
            $table->boolean('acepta_delivery')->default(true);
            $table->boolean('acepta_recoger')->default(true);
            $table->boolean('acepta_pago_online')->default(false);

            // Estadísticas
            $table->decimal('calificacion', 3, 2)->default(0);
            $table->integer('total_resenas')->default(0);
            $table->integer('total_pedidos')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['activo', 'abierto']);
            $table->index('calificacion');
        });

        // Zonas de delivery por restaurante
        Schema::create('zonas_delivery_restaurante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectores')->cascadeOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->cascadeOnDelete();
            $table->decimal('tarifa_delivery', 10, 2)->default(0);
            $table->integer('tiempo_estimado')->default(30)->comment('Minutos estimados');
            $table->decimal('pedido_minimo', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Horarios especiales (feriados, días especiales)
        Schema::create('horarios_especiales_restaurante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_apertura')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->boolean('cerrado')->default(false);
            $table->string('motivo')->nullable();
            $table->enum('tipo', ['feriado', 'evento', 'vacaciones', 'otro'])->default('otro');
            $table->timestamps();

            $table->unique(['restaurante_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_especiales_restaurante');
        Schema::dropIfExists('zonas_delivery_restaurante');
        Schema::dropIfExists('restaurantes');
    }
};
