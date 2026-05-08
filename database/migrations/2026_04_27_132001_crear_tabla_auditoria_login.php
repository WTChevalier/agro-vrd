<?php

/**
 * Migration: crear tabla auditoria_login
 *
 * Sprint 132B — Trail de intentos de login al panel admin (exitosos y
 * fallidos). Distinto a auditoria_actividad (119A) que trackea cambios
 * a modelos: este captura SOLO eventos de auth.
 *
 * Útil para:
 *   - Detectar intentos de brute-force (múltiples FAIL desde misma IP)
 *   - Compliance: quién accedió cuando (GDPR Art. 32)
 *   - Forensics si pasa algo raro
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auditoria_login')) return;

        Schema::create('auditoria_login', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('usuario_id')->nullable()->index();
            $t->string('email_intento', 150)->index();   // email tal cual lo escribieron
            $t->boolean('exito');
            $t->string('motivo', 60)->nullable();        // 'wrong_password' | 'unknown_user' | 'banned' | …
            $t->string('ip', 45)->nullable()->index();
            $t->string('user_agent', 300)->nullable();
            $t->timestamps();

            $t->index(['exito', 'created_at']);
            $t->index(['email_intento', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_login');
    }
};
