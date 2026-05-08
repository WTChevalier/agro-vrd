<?php

/**
 * Migración: completar columnas 2FA en users (tenant apps)
 *
 * Sprint 674 — Replica de Sprint 139Q adaptada para apps tenant que usan
 * tabla `users` (default Laravel) en lugar de `usuarios` (Hub Corporativo).
 *
 * Agrega columnas idempotentes que Filament 5 MFA + Fortify
 * TwoFactorAuthenticatable requieren:
 *   - two_factor_secret              (encrypted)
 *   - two_factor_recovery_codes      (encrypted JSON array)
 *   - two_factor_confirmed_at        (timestamp opt-in)
 *
 * Skip si ya existen (idempotente). Compatible con Laravel 11+ y Filament 5.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Algunas apps tienen 'users', otras 'usuarios'. Detectar.
        $tabla = Schema::hasTable('users') ? 'users'
            : (Schema::hasTable('usuarios') ? 'usuarios' : null);

        if (! $tabla) {
            return; // No hay tabla compatible — skip
        }

        Schema::table($tabla, function (Blueprint $t) use ($tabla) {
            if (! Schema::hasColumn($tabla, 'two_factor_secret')) {
                $t->text('two_factor_secret')->nullable();
            }
            if (! Schema::hasColumn($tabla, 'two_factor_recovery_codes')) {
                $t->text('two_factor_recovery_codes')->nullable();
            }
            if (! Schema::hasColumn($tabla, 'two_factor_confirmed_at')) {
                $t->timestamp('two_factor_confirmed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        $tabla = Schema::hasTable('users') ? 'users'
            : (Schema::hasTable('usuarios') ? 'usuarios' : null);

        if (! $tabla) {
            return;
        }

        Schema::table($tabla, function (Blueprint $t) use ($tabla) {
            $cols = ['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'];
            foreach ($cols as $c) {
                if (Schema::hasColumn($tabla, $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
