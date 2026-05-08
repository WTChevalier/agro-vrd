<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas de Planes y Suscripciones
 *
 * Crea las tablas para:
 * - Planes disponibles
 * - Suscripciones de restaurantes
 * - Historial de cambios de plan
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // PLANES
        // Definición de planes disponibles para restaurantes
        // =====================================================================
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // 'gratis', 'basico', 'premium', 'empresarial'
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_corta')->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->string('moneda', 3)->default('DOP'); // Peso Dominicano
            $table->string('periodo', 20)->default('mensual'); // 'mensual', 'trimestral', 'anual'
            $table->integer('dias_periodo')->default(30);
            $table->json('funciones')->nullable(); // JSON con funciones incluidas {codigo: true/false}
            $table->json('limites')->nullable(); // JSON con límites {platos_max: 15, fotos_por_plato: 3}
            $table->json('beneficios')->nullable(); // Lista de beneficios para mostrar
            $table->decimal('descuento_anual', 5, 2)->default(0); // % descuento si paga anual
            $table->boolean('es_popular')->default(false); // Destacar en UI
            $table->boolean('es_publico')->default(true); // Visible para nuevos registros
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->string('color', 20)->nullable(); // Color del badge
            $table->string('icono', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo');
            $table->index('es_publico');
            $table->index('orden');
        });

        // =====================================================================
        // SUSCRIPCIONES
        // Registro de suscripciones activas de restaurantes
        // =====================================================================
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_proximo_cobro')->nullable();
            $table->string('estado', 30)->default('activa'); // 'activa', 'vencida', 'cancelada', 'suspendida', 'prueba'
            $table->decimal('precio_acordado', 10, 2); // Precio al momento de contratar
            $table->decimal('descuento_aplicado', 5, 2)->default(0);
            $table->string('periodo_contratado', 20); // 'mensual', 'trimestral', 'anual'
            $table->boolean('renovacion_automatica')->default(false);
            $table->text('notas')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('cancelada_at')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_fin');
            $table->index('fecha_proximo_cobro');
            $table->index(['restaurante_id', 'estado']);
        });

        // =====================================================================
        // HISTORIAL DE SUSCRIPCIONES
        // Cambios de plan y eventos importantes
        // =====================================================================
        Schema::create('historial_suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suscripcion_id')->constrained('suscripciones')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->string('evento', 50); // 'creada', 'renovada', 'upgrade', 'downgrade', 'cancelada', 'suspendida', 'reactivada'
            $table->foreignId('plan_anterior_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->foreignId('plan_nuevo_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->json('datos_adicionales')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('realizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index('evento');
            $table->index(['restaurante_id', 'created_at']);
        });

        // =====================================================================
        // OVERRIDE DE FUNCIONES POR RESTAURANTE
        // Activar/desactivar funciones específicas fuera del plan
        // =====================================================================
        Schema::create('configuracion_restaurantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('funcion_id')->constrained('funciones_sistema')->cascadeOnDelete();
            $table->boolean('activo')->default(true); // true = activar, false = desactivar
            $table->json('valor_personalizado')->nullable(); // Si la función tiene valores configurables
            $table->text('motivo')->nullable(); // Por qué se activó/desactivó
            $table->date('fecha_expiracion')->nullable(); // Si es temporal
            $table->foreignId('autorizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->unique(['restaurante_id', 'funcion_id']);
            $table->index('activo');
            $table->index('fecha_expiracion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_restaurantes');
        Schema::dropIfExists('historial_suscripciones');
        Schema::dropIfExists('suscripciones');
        Schema::dropIfExists('planes');
    }
};
