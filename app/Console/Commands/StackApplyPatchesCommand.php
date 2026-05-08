<?php

/**
 * Sprint 139KK — Aplica solo paquetes con bump=patch (seguros).
 * Backup automático + smoke test + rollback si algo falla.
 *
 * Uso: php artisan stack:apply-patches
 *
 * Última revisión: 2026-04-28 — WT Chevalier (Founder)
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StackApplyPatchesCommand extends Command
{
    protected $signature = 'stack:apply-patches {--dry-run : Solo mostrar qué se haría, sin ejecutar}';
    protected $description = 'Aplica parches seguros (semver patch) con backup + rollback automático.';

    public function handle(): int
    {
        // 1. Cargar último scan
        $upPath = storage_path('app/stack-updates.json');
        if (!file_exists($upPath)) {
            $this->error('No hay scan disponible. Ejecuta stack:check-updates primero.');
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($upPath), true);
        $patches = collect($data['outdated'] ?? [])
            ->filter(fn ($p) => ($p['bump'] ?? '') === 'patch')
            ->all();

        if (empty($patches)) {
            $this->info('No hay parches pendientes.');
            return self::SUCCESS;
        }

        $names = collect($patches)->pluck('name')->all();
        $this->info('Parches a aplicar (' . count($names) . '):');
        foreach ($patches as $p) {
            $this->line("  - {$p['name']}: {$p['version']} → {$p['latest']}");
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no se ejecutó nada.');
            return self::SUCCESS;
        }

        // 2. Backup
        $base = base_path();
        $stamp = date('Ymd_His');
        $bakJson = "$base/composer.json.bak_pre_patches_$stamp";
        $bakLock = "$base/composer.lock.bak_pre_patches_$stamp";
        if (!@copy("$base/composer.json", $bakJson) || !@copy("$base/composer.lock", $bakLock)) {
            $this->error('No se pudo crear backup. Abortando.');
            return self::FAILURE;
        }
        $this->info("✓ Backup creado: $bakJson");

        // 3. Ejecutar composer update solo de los paquetes patch
        $php = '/usr/local/lsws/lsphp83/bin/php';
        $composer = '/usr/local/bin/composer';
        $namesStr = implode(' ', array_map(fn ($n) => escapeshellarg($n), $names));
        $cmd = "cd " . escapeshellarg($base) . " && $php $composer update $namesStr --no-interaction --no-progress 2>&1";

        $this->info("Ejecutando: composer update " . implode(' ', $names));
        $out = shell_exec($cmd);
        $this->line(substr($out, -2000));

        $exitCode = 0;
        if (str_contains(strtolower($out), 'fatal') || str_contains(strtolower($out), 'could not be resolved')) {
            $exitCode = 1;
        }

        // 4. Smoke test
        $smokeOk = $this->smokeTest();

        // 5. Decidir: commit o rollback
        if ($exitCode !== 0 || !$smokeOk) {
            $this->error('FALLO — restaurando desde backup...');
            @copy($bakJson, "$base/composer.json");
            @copy($bakLock, "$base/composer.lock");
            shell_exec("cd " . escapeshellarg($base) . " && $php $composer install --no-interaction 2>&1");
            $this->logHistorial([
                'action'   => 'apply-patches',
                'status'   => 'rollback',
                'reason'   => $exitCode !== 0 ? 'composer error' : 'smoke test failed',
                'packages' => $names,
                'stamp'    => $stamp,
                'output'   => substr($out, -1000),
            ]);
            $this->error('Rollback completado.');
            return self::FAILURE;
        }

        // 6. Limpiar caches
        shell_exec("cd " . escapeshellarg($base) . " && $php artisan optimize:clear 2>&1");

        // 7. Log audit
        $this->logHistorial([
            'action'   => 'apply-patches',
            'status'   => 'ok',
            'packages' => $names,
            'count'    => count($names),
            'stamp'    => $stamp,
        ]);

        // 8. Re-scan para refrescar la página
        $this->call('stack:check-updates', ['--silent' => true]);

        $this->info('✓ Parches aplicados exitosamente.');
        return self::SUCCESS;
    }

    private function smokeTest(): bool
    {
        try {
            $url = 'https://' . request()->getHost() . '/';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            // 200, 301, 302 OK. 5xx malo.
            return $code > 0 && $code < 500;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function logHistorial(array $entry): void
    {
        try {
            $logPath = storage_path('app/stack-update-history.json');
            $history = file_exists($logPath) ? (json_decode(file_get_contents($logPath), true) ?: []) : [];
            $entry['at'] = now()->toIso8601String();
            $entry['user'] = auth()->user()?->email ?? 'cli';
            $history[] = $entry;
            // Retener últimas 100 entradas
            if (count($history) > 100) $history = array_slice($history, -100);
            file_put_contents($logPath, json_encode($history, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::warning('No se pudo escribir stack-update-history.json: ' . $e->getMessage());
        }
    }
}
