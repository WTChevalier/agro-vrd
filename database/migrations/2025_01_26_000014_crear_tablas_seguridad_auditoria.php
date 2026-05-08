<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tablas de Seguridad y Auditoría
 *
 * Crea las tablas para:
 * - Sesiones de usuario
 * - Intentos de acceso
 * - Alertas de seguridad
 * - Auditoría de acciones
 * - Configuración 2FA
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // DISPOSITIVOS CONOCIDOS
        // Dispositivos desde los que el usuario ha accedido
        // =====================================================================
        Schema::create('dispositivos_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('huella_dispositivo', 64)->nullable(); // Hash único del dispositivo
            $table->string('nombre_dispositivo')->nullable(); // "iPhone 14 Pro", "Chrome en Windows"
            $table->string('tipo', 30)->nullable(); // 'movil', 'desktop', 'tablet'
            $table->string('sistema_operativo', 50)->nullable();
            $table->string('navegador', 50)->nullable();
            $table->string('version_navegador', 20)->nullable();
            $table->boolean('es_confiable')->default(false);
            $table->timestamp('primera_vez_at');
            $table->timestamp('ultima_vez_at');
            $table->integer('veces_usado')->default(1);
            $table->string('ultima_ip', 45)->nullable();
            $table->string('ultima_ubicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['usuario_id', 'huella_dispositivo']);
            $table->index(['usuario_id', 'es_confiable']);
        });

        // =====================================================================
        // SESIONES DE USUARIO
        // Control de sesiones activas
        // =====================================================================
        Schema::create('sesiones_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->foreignId('dispositivo_id')->nullable()->constrained('dispositivos_usuario')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('ubicacion')->nullable();
            $table->json('ubicacion_geo')->nullable(); // lat, lng, ciudad, país
            $table->boolean('es_sospechosa')->default(false);
            $table->string('motivo_sospecha')->nullable();
            $table->timestamp('ultimo_acceso_at');
            $table->timestamp('expira_at');
            $table->string('cerrada_por', 30)->nullable(); // null, 'usuario', 'admin', 'sistema', 'expiracion'
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();

            $table->index('token');
            $table->index(['usuario_id', 'cerrada_at']);
            $table->index('expira_at');
            $table->index('es_sospechosa');
        });

        // =====================================================================
        // INTENTOS DE ACCESO
        // Registro de todos los intentos de login y acceso
        // =====================================================================
        Schema::create('intentos_acceso', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30); // 'login', 'accion_denegada', 'recurso_no_autorizado', '2fa_fallido'
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('email_intentado')->nullable(); // Si fue login fallido
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('ubicacion')->nullable();
            $table->boolean('exitoso')->default(false);
            $table->string('motivo_fallo', 100)->nullable(); // 'contrasena_incorrecta', 'usuario_no_existe', 'cuenta_bloqueada', 'sin_permiso', '2fa_incorrecto'
            $table->string('recurso_solicitado')->nullable(); // URL o acción intentada
            $table->json('datos_adicionales')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('exitoso');
            $table->index('ip_address');
            $table->index(['usuario_id', 'created_at']);
            $table->index('email_intentado');
        });

        // =====================================================================
        // CONFIGURACIÓN 2FA
        // Autenticación de dos factores
        // =====================================================================
        Schema::create('configuracion_2fa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('metodo', 30); // 'sms', 'whatsapp', 'authenticator', 'email'
            $table->string('telefono', 20)->nullable(); // Si es SMS/WhatsApp
            $table->string('correo_alternativo')->nullable(); // Si es email
            $table->text('secreto')->nullable(); // Para authenticator (encriptado)
            $table->json('codigos_respaldo')->nullable(); // Códigos de emergencia (encriptados)
            $table->boolean('activo')->default(false);
            $table->boolean('es_principal')->default(false);
            $table->timestamp('verificado_at')->nullable();
            $table->timestamp('ultimo_uso_at')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'activo']);
            $table->index(['usuario_id', 'es_principal']);
        });

        // =====================================================================
        // CÓDIGOS 2FA TEMPORALES
        // Códigos enviados para verificación
        // =====================================================================
        Schema::create('codigos_2fa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('config_2fa_id')->nullable()->constrained('configuracion_2fa')->cascadeOnDelete();
            $table->string('codigo', 10); // El código enviado
            $table->string('tipo', 30); // 'login', 'verificacion', 'cambio_contrasena', 'accion_sensible'
            $table->string('enviado_a')->nullable(); // Teléfono o correo
            $table->string('metodo', 30); // 'sms', 'whatsapp', 'email'
            $table->timestamp('expira_at');
            $table->boolean('usado')->default(false);
            $table->timestamp('usado_at')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->string('ip_solicitud', 45)->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'codigo', 'usado']);
            $table->index('expira_at');
        });

        // =====================================================================
        // ALERTAS DE SEGURIDAD
        // Alertas generadas por el sistema
        // =====================================================================
        Schema::create('alertas_seguridad', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50); // 'login_inusual', 'intentos_fallidos', 'acceso_denegado', 'exportacion_masiva', etc.
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('personal_id')->nullable()->constrained('personal')->nullOnDelete();
            $table->string('severidad', 20); // 'baja', 'media', 'alta', 'critica'
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->json('datos')->nullable(); // Datos relevantes de la alerta
            $table->string('estado', 30)->default('nueva'); // 'nueva', 'vista', 'investigando', 'resuelta', 'falsa_alarma'
            $table->foreignId('atendida_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('atendida_at')->nullable();
            $table->text('notas_resolucion')->nullable();
            $table->string('accion_tomada', 100)->nullable();
            $table->boolean('notificada')->default(false);
            $table->timestamp('notificada_at')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('severidad');
            $table->index('estado');
            $table->index(['usuario_id', 'created_at']);
            $table->index('notificada');
        });

        // =====================================================================
        // AUDITORÍA
        // Registro de todas las acciones importantes
        // =====================================================================
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('personal_id')->nullable()->constrained('personal')->nullOnDelete();
            $table->string('accion', 50); // 'crear', 'editar', 'eliminar', 'ver', 'exportar', 'login', 'logout'
            $table->string('modulo', 50); // 'restaurantes', 'finanzas', 'personal', etc.
            $table->string('submodulo', 50)->nullable();
            $table->string('entidad_tipo', 100)->nullable(); // Nombre del modelo
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('descripcion', 500)->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->json('datos_extra')->nullable(); // Contexto adicional
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ubicacion')->nullable();
            $table->foreignId('sesion_id')->nullable()->constrained('sesiones_usuario')->nullOnDelete();
            $table->boolean('es_sensible')->default(false); // Acción que requiere atención especial
            $table->timestamps();

            $table->index(['usuario_id', 'created_at']);
            $table->index(['modulo', 'accion']);
            $table->index(['entidad_tipo', 'entidad_id']);
            $table->index('es_sensible');
            $table->index('created_at');
        });

        // =====================================================================
        // BLOQUEOS DE CUENTA
        // Registro de bloqueos por seguridad
        // =====================================================================
        Schema::create('bloqueos_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo', 30); // 'intentos_fallidos', 'sospecha_fraude', 'manual', 'inactividad'
            $table->string('motivo', 200);
            $table->timestamp('bloqueado_at');
            $table->timestamp('expira_at')->nullable(); // null = permanente hasta revisión
            $table->timestamp('desbloqueado_at')->nullable();
            $table->foreignId('bloqueado_por')->nullable()->constrained('usuarios')->nullOnDelete(); // null = sistema
            $table->foreignId('desbloqueado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('notas_desbloqueo')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'desbloqueado_at']);
            $table->index('expira_at');
        });

        // =====================================================================
        // POLÍTICAS DE CONTRASEÑA
        // Historial de contraseñas para evitar repetición
        // =====================================================================
        Schema::create('historial_contrasenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('contrasena_hash'); // Hash de la contraseña anterior
            $table->timestamps();

            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_contrasenas');
        Schema::dropIfExists('bloqueos_cuenta');
        Schema::dropIfExists('auditoria');
        Schema::dropIfExists('alertas_seguridad');
        Schema::dropIfExists('codigos_2fa');
        Schema::dropIfExists('configuracion_2fa');
        Schema::dropIfExists('intentos_acceso');
        Schema::dropIfExists('sesiones_usuario');
        Schema::dropIfExists('dispositivos_usuario');
    }
};
