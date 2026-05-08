<?php

/**
 * Sprint 139KK — Comando que escanea actualizaciones disponibles del stack.
 *
 * Ejecuta:
 *   - composer outdated -D --format=json (paquetes con updates disponibles)
 *   - composer audit --format=json (security advisories)
 *   - Packagist API para PHP/Laravel/Filament latest stable
 *
 * Guarda resultado en storage/app/stack-updates.json para que el Monitor Sistema page lo lea.
 *
 * Cron: diario a las 3am.
 *
 * Última revisión: 2026-04-28 — WT Chevalier (Founder)
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StackCheckUpdatesCommand extends Command
{
    protected $signature = 'stack:check-updates {--silent : No mostrar output}';
    protected $description = 'Escanea actualizaciones disponibles del stack (Composer + Packagist) y guarda JSON.';

    public function handle(): int
    {
        $silent = $this->option('silent');
        if (!$silent) $this->info('Escaneando actualizaciones del stack...');

        $resultado = [
            'scan_at'   => now()->toIso8601String(),
            'php'       => $this->infoPhp(),
            'laravel'   => $this->infoPaquete('laravel/framework'),
            'filament'  => $this->infoPaquete('filament/filament'),
            'livewire'  => $this->infoPaquete('livewire/livewire'),
            'tinker'    => $this->infoPaquete('laravel/tinker'),
            'outdated'  => $this->composerOutdated(),
            'security'  => $this->composerAudit(),
            'resumen'   => null, // se calcula al final
        ];

        // Calcular resumen agregado
        $resultado['resumen'] = $this->calcularResumen($resultado);

        // Guardar
        $path = storage_path('app/stack-updates.json');
        @file_put_contents($path, json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        @chmod($path, 0644);

        if (!$silent) {
            $r = $resultado['resumen'];
            $this->info("Scan completo:");
            $this->line("  Composer outdated: {$r['outdated_total']} ({$r['patch']} parches, {$r['minor']} minors, {$r['major']} majors)");
            $this->line("  Security advisories: {$r['security_total']}");
            $this->line("  PHP: " . ($resultado['php']['actual'] ?? '?') . " / latest " . ($resultado['php']['latest'] ?? '?'));
            $this->line("  Laravel: " . ($resultado['laravel']['actual'] ?? '?') . " / latest " . ($resultado['laravel']['latest'] ?? '?'));
            $this->line("  Filament: " . ($resultado['filament']['actual'] ?? '?') . " / latest " . ($resultado['filament']['latest'] ?? '?'));
        }

        return self::SUCCESS;
    }

    /** Versión PHP actual + latest desde php.net (best effort). */
    private function infoPhp(): array
    {
        $actual = PHP_VERSION;
        $latest = null;
        try {
            // php.net no tiene API JSON pública robusta — usamos defaults conocidos
            // PHP 8.4.x es el latest stable a 2026-04
            // Mejor: dejar null y que el page muestre solo current
            $resp = Http::timeout(10)->get('https://www.php.net/releases/index.php?json=1&max=5');
            if ($resp->ok()) {
                $data = $resp->json();
                $latest = $data['8']['version'] ?? null; // PHP 8 latest
            }
        } catch (\Throwable $e) {}
        return ['actual' => $actual, 'latest' => $latest];
    }

    /** Versión actual + latest desde Packagist para un paquete específico. */
    private function infoPaquete(string $nombre): array
    {
        $actual = $this->versionInstalada($nombre);
        $latest = null;
        try {
            $resp = Http::timeout(10)->get("https://repo.packagist.org/p2/{$nombre}.json");
            if ($resp->ok()) {
                $data = $resp->json();
                $versions = $data['packages'][$nombre] ?? [];
                // primer elemento = latest
                $stable = collect($versions)->first(fn($v) => !str_contains($v['version'] ?? '', 'dev') && !str_contains($v['version'] ?? '', 'alpha') && !str_contains($v['version'] ?? '', 'beta') && !str_contains($v['version'] ?? '', 'RC'));
                $latest = $stable['version'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::debug("Packagist lookup falló para {$nombre}: " . $e->getMessage());
        }
        $bump = $this->tipoBump($actual, $latest);
        return ['actual' => $actual, 'latest' => $latest, 'bump' => $bump];
    }

    /** Lee composer.lock y obtiene la versión instalada del paquete. */
    private function versionInstalada(string $nombre): ?string
    {
        try {
            $lockPath = base_path('composer.lock');
            if (!file_exists($lockPath)) return null;
            $lock = json_decode(file_get_contents($lockPath), true);
            foreach ($lock['packages'] ?? [] as $p) {
                if (($p['name'] ?? '') === $nombre) return $p['version'] ?? null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /** Ejecuta composer outdated y devuelve array de paquetes outdated. */
    private function composerOutdated(): array
    {
        try {
            $php = '/usr/local/lsws/lsphp83/bin/php';
            $composer = '/usr/local/bin/composer';
            $base = base_path();
            $cmd = "cd {$base} && {$php} {$composer} outdated -D --format=json --no-interaction 2>&1";
            $out = @shell_exec($cmd);
            if (!$out) return [];
            // El output puede tener PHP warnings antes del JSON real
            $jsonStart = strpos($out, '{');
            if ($jsonStart === false) return [];
            $json = json_decode(substr($out, $jsonStart), true);
            $paquetes = $json['installed'] ?? [];
            // Enriquecer con tipo de bump
            return collect($paquetes)->map(function ($p) {
                $p['bump'] = $this->tipoBump($p['version'] ?? '', $p['latest'] ?? '');
                return $p;
            })->all();
        } catch (\Throwable $e) {
            Log::warning('composer outdated falló: ' . $e->getMessage());
            return [];
        }
    }

    /** Ejecuta composer audit y devuelve security advisories. */
    private function composerAudit(): array
    {
        try {
            $php = '/usr/local/lsws/lsphp83/bin/php';
            $composer = '/usr/local/bin/composer';
            $base = base_path();
            $cmd = "cd {$base} && {$php} {$composer} audit --format=json --no-interaction 2>&1";
            $out = @shell_exec($cmd);
            if (!$out) return [];
            $jsonStart = strpos($out, '{');
            if ($jsonStart === false) return [];
            $json = json_decode(substr($out, $jsonStart), true);
            $advisories = [];
            foreach ($json['advisories'] ?? [] as $pkgName => $list) {
                foreach ($list as $adv) {
                    $advisories[] = [
                        'paquete'       => $pkgName,
                        'titulo'        => $adv['title'] ?? '',
                        'cve'           => $adv['cve'] ?? null,
                        'severidad'     => $adv['severity'] ?? 'unknown',
                        'reportado'     => $adv['reportedAt'] ?? null,
                        'link'          => $adv['link'] ?? null,
                        'version_segura' => $adv['affectedVersions'] ?? null,
                    ];
                }
            }
            return $advisories;
        } catch (\Throwable $e) {
            Log::warning('composer audit falló: ' . $e->getMessage());
            return [];
        }
    }

    /** Determina si el bump entre v1 → v2 es patch/minor/major/none. */
    private function tipoBump(?string $v1, ?string $v2): string
    {
        if (!$v1 || !$v2) return 'unknown';
        $a = $this->parsearVersion($v1);
        $b = $this->parsearVersion($v2);
        if (!$a || !$b) return 'unknown';
        if ($a[0] !== $b[0]) return 'major';
        if ($a[1] !== $b[1]) return 'minor';
        if ($a[2] !== $b[2]) return 'patch';
        return 'none';
    }

    /** Parsea "v3.3.50" o "12.57.0" → [3, 3, 50] o [12, 57, 0]. */
    private function parsearVersion(string $v): ?array
    {
        $v = ltrim($v, 'v');
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)/', $v, $m)) return null;
        return [(int)$m[1], (int)$m[2], (int)$m[3]];
    }

    /** Resumen agregado para las stats cards. */
    private function calcularResumen(array $r): array
    {
        $bumps = ['patch' => 0, 'minor' => 0, 'major' => 0, 'unknown' => 0];
        foreach ($r['outdated'] as $p) {
            $b = $p['bump'] ?? 'unknown';
            $bumps[$b] = ($bumps[$b] ?? 0) + 1;
        }
        return [
            'outdated_total' => count($r['outdated']),
            'patch'          => $bumps['patch'],
            'minor'          => $bumps['minor'],
            'major'          => $bumps['major'],
            'unknown'        => $bumps['unknown'],
            'security_total' => count($r['security']),
        ];
    }
}
