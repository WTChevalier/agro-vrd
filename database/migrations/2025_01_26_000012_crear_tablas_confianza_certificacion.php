<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas de Confianza y Certificación
 *
 * Crea las tablas para:
 * - Niveles de confianza (interno)
 * - Certificaciones públicas (A, B, C, D, E)
 * - Puntuaciones y evaluaciones
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // NIVELES DE CONFIANZA (Interno)
        // Para decidir qué funciones puede usar el restaurante
        // =====================================================================
        Schema::create('niveles_confianza', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique(); // 'nuevo', 'observacion', 'confiable', 'socio'
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('color', 20); // 'rojo', 'amarillo', 'verde', 'dorado'
            $table->string('icono', 50)->nullable();
            $table->integer('puntos_minimo')->default(0);
            $table->integer('puntos_maximo')->default(100);
            $table->json('funciones_permitidas')->nullable(); // Funciones adicionales por nivel
            $table->json('restricciones')->nullable(); // Restricciones del nivel
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('orden');
        });

        // =====================================================================
        // CERTIFICACIONES (Público)
        // Lo que ven los clientes
        // =====================================================================
        Schema::create('certificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique(); // 'A', 'B', 'C', 'D', 'E'
            $table->string('nombre', 100); // 'Excelencia', 'Muy Bueno', etc.
            $table->text('descripcion')->nullable();
            $table->string('descripcion_corta', 150)->nullable(); // Para badges
            $table->string('color', 20);
            $table->string('icono', 50)->nullable();
            $table->string('imagen_badge')->nullable(); // URL del badge
            $table->integer('puntos_minimo')->default(0);
            $table->integer('puntos_maximo')->default(100);
            $table->json('beneficios')->nullable(); // Lista de beneficios
            $table->decimal('descuento_plan', 5, 2)->default(0); // % descuento en plan
            $table->integer('prioridad_busqueda')->default(0); // Mayor = aparece primero
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('orden');
        });

        // =====================================================================
        // CRITERIOS DE CERTIFICACIÓN
        // Qué se evalúa para la certificación
        // =====================================================================
        Schema::create('criterios_certificacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // 'calidad_comida', 'tiempo_entrega', etc.
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('puntos_maximo')->default(20);
            $table->decimal('peso', 5, 2)->default(1.00); // Peso en cálculo final
            $table->string('tipo_calculo', 30)->default('promedio'); // 'promedio', 'porcentaje', 'manual'
            $table->json('formula')->nullable(); // Cómo se calcula automáticamente
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('orden');
        });

        // =====================================================================
        // PUNTUACIONES DE RESTAURANTES
        // Puntuación actual de cada restaurante
        // =====================================================================
        Schema::create('puntuaciones_restaurantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->unique()->constrained('restaurantes')->cascadeOnDelete();

            // Puntuación de confianza (interno)
            $table->integer('puntuacion_confianza')->default(0);
            $table->integer('puntos_antiguedad')->default(0);
            $table->integer('puntos_pagos')->default(0);
            $table->integer('puntos_pedidos')->default(0);
            $table->integer('puntos_calificacion')->default(0);
            $table->integer('puntos_verificaciones')->default(0);
            $table->integer('puntos_incidentes')->default(0); // Generalmente negativo
            $table->foreignId('nivel_confianza_id')->nullable()->constrained('niveles_confianza')->nullOnDelete();

            // Puntuación de certificación (público)
            $table->integer('puntuacion_certificacion')->default(0);
            $table->integer('puntos_calidad_comida')->default(0);
            $table->integer('puntos_tiempo_entrega')->default(0);
            $table->integer('puntos_higiene')->default(0);
            $table->integer('puntos_servicio')->default(0);
            $table->integer('puntos_informacion')->default(0);
            $table->foreignId('certificacion_id')->nullable()->constrained('certificaciones')->nullOnDelete();

            $table->timestamp('confianza_calculada_at')->nullable();
            $table->timestamp('certificacion_calculada_at')->nullable();
            $table->timestamps();

            $table->index('puntuacion_confianza');
            $table->index('puntuacion_certificacion');
            $table->index('nivel_confianza_id');
            $table->index('certificacion_id');
        });

        // =====================================================================
        // HISTORIAL DE PUNTUACIÓN (Confianza)
        // Registro de cambios en puntuación
        // =====================================================================
        Schema::create('historial_puntuacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->string('tipo', 50); // 'pago', 'pedido', 'queja', 'verificacion', 'antiguedad', etc.
            $table->integer('puntos'); // Positivo o negativo
            $table->integer('puntuacion_anterior');
            $table->integer('puntuacion_nueva');
            $table->text('descripcion')->nullable();
            $table->string('referencia_tipo', 50)->nullable(); // 'pago', 'incidente', 'verificacion'
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index(['restaurante_id', 'created_at']);
            $table->index('tipo');
            $table->index(['referencia_tipo', 'referencia_id']);
        });

        // =====================================================================
        // EVALUACIONES DE CERTIFICACIÓN
        // Evaluaciones periódicas para certificar restaurantes
        // =====================================================================
        Schema::create('evaluaciones_certificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('evaluado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('tipo', 30)->default('periodica'); // 'inicial', 'periodica', 'especial', 'apelacion'
            $table->string('estado', 30)->default('pendiente'); // 'pendiente', 'en_proceso', 'completada', 'aprobada', 'rechazada'

            // Puntuaciones por criterio
            $table->integer('puntos_calidad_comida')->default(0);
            $table->integer('puntos_tiempo_entrega')->default(0);
            $table->integer('puntos_higiene')->default(0);
            $table->integer('puntos_servicio')->default(0);
            $table->integer('puntos_informacion')->default(0);
            $table->integer('puntos_total')->default(0);

            // Resultado
            $table->foreignId('certificacion_anterior_id')->nullable()->constrained('certificaciones')->nullOnDelete();
            $table->foreignId('certificacion_nueva_id')->nullable()->constrained('certificaciones')->nullOnDelete();

            $table->json('detalle_evaluacion')->nullable(); // Desglose detallado
            $table->text('observaciones')->nullable();
            $table->text('recomendaciones')->nullable(); // Cómo mejorar
            $table->date('fecha_evaluacion')->nullable();
            $table->date('proxima_evaluacion')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index(['restaurante_id', 'created_at']);
        });

        // =====================================================================
        // HISTORIAL DE CERTIFICACIONES
        // Cambios en la certificación de restaurantes
        // =====================================================================
        Schema::create('historial_certificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('evaluacion_id')->nullable()->constrained('evaluaciones_certificacion')->nullOnDelete();
            $table->foreignId('certificacion_anterior_id')->nullable()->constrained('certificaciones')->nullOnDelete();
            $table->foreignId('certificacion_nueva_id')->nullable()->constrained('certificaciones')->nullOnDelete();
            $table->string('motivo', 100)->nullable(); // 'evaluacion', 'mejora_automatica', 'penalizacion', etc.
            $table->text('notas')->nullable();
            $table->foreignId('cambiado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index(['restaurante_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_certificaciones');
        Schema::dropIfExists('evaluaciones_certificacion');
        Schema::dropIfExists('historial_puntuacion');
        Schema::dropIfExists('puntuaciones_restaurantes');
        Schema::dropIfExists('criterios_certificacion');
        Schema::dropIfExists('certificaciones');
        Schema::dropIfExists('niveles_confianza');
    }
};
