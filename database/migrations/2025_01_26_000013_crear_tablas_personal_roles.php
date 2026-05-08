<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas de Personal y Roles
 *
 * Crea las tablas para:
 * - Roles del sistema
 * - Permisos
 * - Personal de SazónRD
 * - Tareas de campo
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // PERMISOS
        // Catálogo de todos los permisos disponibles
        // =====================================================================
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique(); // 'restaurantes.ver', 'finanzas.exportar', etc.
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('modulo', 50); // 'restaurantes', 'finanzas', 'personal', etc.
            $table->string('grupo', 50)->nullable(); // Subgrupo dentro del módulo
            $table->string('tipo', 30)->default('accion'); // 'accion', 'lectura', 'escritura', 'eliminacion'
            $table->boolean('es_sensible')->default(false); // Requiere auditoría especial
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index('modulo');
            $table->index(['modulo', 'grupo']);
        });

        // =====================================================================
        // ROLES
        // Definición de roles con sus permisos
        // =====================================================================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // 'super_admin', 'admin', 'contador', etc.
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 30)->default('interno'); // 'interno' (SazónRD), 'restaurante', 'cliente'
            $table->json('permisos')->nullable(); // Array de códigos de permisos
            $table->boolean('es_sistema')->default(false); // true = no se puede eliminar
            $table->boolean('requiere_2fa')->default(false);
            $table->boolean('acceso_total_zonas')->default(false); // true = ve todas las zonas
            $table->string('color', 20)->nullable();
            $table->string('icono', 50)->nullable();
            $table->integer('nivel_jerarquia')->default(0); // Mayor = más autoridad
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('activo');
        });

        // =====================================================================
        // ROL-PERMISO (tabla pivote)
        // Relación muchos a muchos entre roles y permisos
        // =====================================================================
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['rol_id', 'permiso_id']);
        });

        // =====================================================================
        // PERSONAL SAZONRD
        // Empleados y colaboradores de SazónRD
        // =====================================================================
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('roles');
            $table->string('codigo_empleado', 20)->unique()->nullable(); // SR-001, SR-002
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('cedula', 20)->unique()->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('telefono_emergencia', 20)->nullable();
            $table->string('direccion')->nullable();
            $table->string('zona_tipo', 50)->nullable(); // 'nacional', 'provincia', 'municipio'
            $table->unsignedBigInteger('zona_id')->nullable(); // ID de la zona asignada
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->string('estado', 30)->default('activo'); // 'activo', 'vacaciones', 'licencia', 'suspendido', 'inactivo'
            $table->json('permisos_extra')->nullable(); // Permisos adicionales fuera del rol
            $table->json('restricciones')->nullable(); // Permisos quitados del rol
            $table->json('horario_trabajo')->nullable(); // Horario esperado
            $table->text('notas')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('personal')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('rol_id');
            $table->index(['zona_tipo', 'zona_id']);
            $table->index('supervisor_id');
        });

        // =====================================================================
        // TAREAS DE CAMPO
        // Asignación de tareas a personal de campo
        // =====================================================================
        Schema::create('tareas_campo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personal')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->nullable()->constrained('restaurantes')->nullOnDelete();
            $table->string('tipo', 50); // 'verificacion', 'cobro', 'soporte', 'seguimiento', 'entrega_material'
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->string('prioridad', 20)->default('media'); // 'baja', 'media', 'alta', 'urgente'
            $table->string('estado', 30)->default('pendiente'); // 'pendiente', 'en_proceso', 'completada', 'cancelada', 'reprogramada'
            $table->date('fecha_programada')->nullable();
            $table->date('fecha_limite')->nullable();
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('completada_at')->nullable();
            $table->json('datos_tarea')->nullable(); // Datos específicos según tipo
            $table->json('resultado')->nullable(); // Resultado de la tarea
            $table->text('notas_completado')->nullable();
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('reasignado_a')->nullable()->constrained('personal')->nullOnDelete();
            $table->timestamps();

            $table->index('tipo');
            $table->index('estado');
            $table->index('prioridad');
            $table->index('fecha_programada');
            $table->index('fecha_limite');
            $table->index(['personal_id', 'estado']);
        });

        // =====================================================================
        // VERIFICACIONES DE CAMPO
        // Verificaciones realizadas a restaurantes
        // =====================================================================
        Schema::create('verificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('personal_id')->constrained('personal');
            $table->foreignId('tarea_id')->nullable()->constrained('tareas_campo')->nullOnDelete();
            $table->string('tipo', 30); // 'inicial', 'seguimiento', 'especial', 'queja'
            $table->string('estado', 30)->default('programada'); // 'programada', 'en_proceso', 'completada', 'cancelada'
            $table->date('fecha_programada')->nullable();
            $table->timestamp('fecha_realizada')->nullable();
            $table->string('resultado', 30)->nullable(); // 'aprobado', 'aprobado_condiciones', 'rechazado', 'pendiente_revision'

            // Datos de la verificación
            $table->json('formulario')->nullable(); // Respuestas del formulario
            $table->json('fotos')->nullable(); // URLs de fotos tomadas
            $table->json('ubicacion_gps')->nullable(); // Coordenadas donde se hizo

            // Evaluación
            $table->text('observaciones')->nullable();
            $table->text('condiciones')->nullable(); // Si fue aprobado con condiciones
            $table->text('motivo_rechazo')->nullable();
            $table->integer('puntos_otorgados')->default(0);

            // Aprobación
            $table->foreignId('aprobado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->text('notas_aprobacion')->nullable();

            $table->date('proxima_verificacion')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('estado');
            $table->index('resultado');
            $table->index(['restaurante_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verificaciones');
        Schema::dropIfExists('tareas_campo');
        Schema::dropIfExists('personal');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permisos');
    }
};
