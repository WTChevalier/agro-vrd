<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Agregar campos de Plan, Confianza y Certificación a Restaurantes
 *
 * Conecta la tabla existente de restaurantes con el nuevo sistema modular
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            // =====================================================================
            // CAMPOS DE SUSCRIPCIÓN Y PLAN
            // =====================================================================
            $table->foreignId('plan_id')->nullable()->after('sector_id')
                ->constrained('planes')->nullOnDelete();
            $table->foreignId('suscripcion_activa_id')->nullable()->after('plan_id')
                ->constrained('suscripciones')->nullOnDelete();

            // =====================================================================
            // CAMPOS DE CONFIANZA (INTERNO)
            // =====================================================================
            $table->foreignId('nivel_confianza_id')->nullable()->after('suscripcion_activa_id')
                ->constrained('niveles_confianza')->nullOnDelete();
            $table->decimal('puntuacion_confianza', 5, 2)->default(0)->after('nivel_confianza_id')
                ->comment('Puntuación calculada automáticamente 0-100');
            $table->timestamp('ultima_evaluacion_confianza_at')->nullable()->after('puntuacion_confianza');

            // =====================================================================
            // CAMPOS DE CERTIFICACIÓN (PÚBLICO)
            // =====================================================================
            $table->foreignId('certificacion_id')->nullable()->after('ultima_evaluacion_confianza_at')
                ->constrained('certificaciones')->nullOnDelete();
            $table->decimal('puntuacion_certificacion', 5, 2)->default(0)->after('certificacion_id')
                ->comment('Puntuación para certificación pública 0-100');
            $table->timestamp('certificacion_otorgada_at')->nullable()->after('puntuacion_certificacion');
            $table->timestamp('certificacion_vence_at')->nullable()->after('certificacion_otorgada_at');

            // =====================================================================
            // CAMPOS DE VERIFICACIÓN
            // =====================================================================
            $table->boolean('verificado')->default(false)->after('certificacion_vence_at')
                ->comment('Si el personal de campo lo ha verificado');
            $table->timestamp('verificado_at')->nullable()->after('verificado');
            $table->foreignId('verificado_por')->nullable()->after('verificado_at')
                ->constrained('personal')->nullOnDelete();
            $table->date('proxima_verificacion')->nullable()->after('verificado_por');

            // =====================================================================
            // CAMPOS DE COBRANZA
            // =====================================================================
            $table->string('estado_cuenta', 30)->default('al_dia')->after('proxima_verificacion')
                ->comment('al_dia, pendiente, moroso, suspendido');
            $table->decimal('saldo_pendiente', 12, 2)->default(0)->after('estado_cuenta');
            $table->date('ultimo_pago_at')->nullable()->after('saldo_pendiente');
            $table->integer('dias_mora')->default(0)->after('ultimo_pago_at');

            // =====================================================================
            // CONFIGURACIÓN MODULAR (OVERRIDE DEL PLAN)
            // =====================================================================
            $table->json('funciones_habilitadas')->nullable()->after('dias_mora')
                ->comment('Override de funciones del plan para este restaurante');
            $table->json('limites_override')->nullable()->after('funciones_habilitadas')
                ->comment('Límites personalizados que sobreescriben el plan');

            // =====================================================================
            // DATOS DE REGISTRO/ONBOARDING
            // =====================================================================
            $table->string('estado_onboarding', 30)->default('pendiente')->after('limites_override')
                ->comment('pendiente, en_proceso, completado, rechazado');
            $table->json('documentos')->nullable()->after('estado_onboarding')
                ->comment('URLs de documentos subidos (RNC, etc.)');
            $table->string('rnc', 20)->nullable()->after('documentos');
            $table->string('nombre_legal')->nullable()->after('rnc');
            $table->text('notas_internas')->nullable()->after('nombre_legal')
                ->comment('Notas del personal de SazónRD');

            // =====================================================================
            // ÍNDICES
            // =====================================================================
            $table->index('nivel_confianza_id');
            $table->index('certificacion_id');
            $table->index('plan_id');
            $table->index('estado_cuenta');
            $table->index('verificado');
            $table->index('estado_onboarding');
        });
    }

    public function down(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['nivel_confianza_id']);
            $table->dropIndex(['certificacion_id']);
            $table->dropIndex(['plan_id']);
            $table->dropIndex(['estado_cuenta']);
            $table->dropIndex(['verificado']);
            $table->dropIndex(['estado_onboarding']);

            // Eliminar foreign keys
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['suscripcion_activa_id']);
            $table->dropForeign(['nivel_confianza_id']);
            $table->dropForeign(['certificacion_id']);
            $table->dropForeign(['verificado_por']);

            // Eliminar columnas
            $table->dropColumn([
                'plan_id',
                'suscripcion_activa_id',
                'nivel_confianza_id',
                'puntuacion_confianza',
                'ultima_evaluacion_confianza_at',
                'certificacion_id',
                'puntuacion_certificacion',
                'certificacion_otorgada_at',
                'certificacion_vence_at',
                'verificado',
                'verificado_at',
                'verificado_por',
                'proxima_verificacion',
                'estado_cuenta',
                'saldo_pendiente',
                'ultimo_pago_at',
                'dias_mora',
                'funciones_habilitadas',
                'limites_override',
                'estado_onboarding',
                'documentos',
                'rnc',
                'nombre_legal',
                'notas_internas',
            ]);
        });
    }
};
