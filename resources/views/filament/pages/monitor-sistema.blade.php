<x-filament-panels::page>
<style>
.ms-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px; }
.ms-card { background: #1a1d24; border-radius: 10px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.05); }
.ms-card h3 { font-size: 12px; font-weight: 600; color: #6b7280; margin: 0 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px; }
.ms-val { font-size: 24px; font-weight: 700; color: #e5e7eb; }
.ms-sub { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.ms-section { background: #1a1d24; border-radius: 10px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.4); margin-bottom: 18px; border: 1px solid rgba(255,255,255,0.05); }
.ms-section h2 { font-size: 16px; font-weight: 700; color: #e5e7eb; margin: 0 0 14px 0; padding-bottom: 10px; border-bottom: 2px solid rgba(255,255,255,0.10); }
.ms-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
.ms-row:last-child { border-bottom: none; }
.ms-label { font-weight: 500; color: #9ca3af; }
.ms-value { font-weight: 600; color: #e5e7eb; }
.ms-bar-bg { background: rgba(255,255,255,0.08); border-radius: 9999px; height: 10px; margin-top: 6px; }
.ms-bar-fill { background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 9999px; height: 10px; transition: width 0.4s ease; }
.ms-ok { color: #10b981; font-weight: 600; }
.ms-warn { color: #f59e0b; font-weight: 600; }
.ms-danger { color: #ef4444; font-weight: 600; }
.ms-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
.ms-table th, .ms-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); }
.ms-table th { color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
.ms-table td { color: #d1d5db; }
.ms-table tr:hover td { background: rgba(255,255,255,0.02); }
.ms-badge { display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.ms-badge-patch { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.ms-badge-minor { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.ms-badge-major { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.ms-badge-unknown { background: rgba(107, 114, 128, 0.15); color: #9ca3af; }
.ms-badge-low { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.ms-badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.ms-badge-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.ms-badge-critical { background: rgba(220, 38, 38, 0.30); color: #fca5a5; }
.ms-update-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 16px; }
.ms-update-card { background: #14171c; border-radius: 8px; padding: 14px; border: 1px solid rgba(255,255,255,0.05); text-align: center; }
.ms-update-card .num { font-size: 28px; font-weight: 800; }
.ms-update-card .lbl { font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px; }
.ms-empty { text-align: center; padding: 30px; color: #6b7280; font-style: italic; }
.ms-link { color: #818cf8; text-decoration: none; }
.ms-link:hover { color: #a5b4fc; text-decoration: underline; }
.ms-meta { font-size: 11px; color: #6b7280; margin-top: 8px; }
</style>

@php
    $phpVersion = phpversion();
    $laravelVersion = app()->version();
    $dbSize = 0;
    $dbTables = 0;
    try {
        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLE STATUS');
        $dbTables = count($tables);
        foreach ($tables as $t) {
            $dbSize += ($t->Data_length ?? 0) + ($t->Index_length ?? 0);
        }
    } catch (\Throwable $e) {}
    $dbSizeMB = round($dbSize / 1024 / 1024, 2);

    $uptime = 'N/A';
    try {
        if (file_exists('/proc/uptime')) {
            $raw = @file_get_contents('/proc/uptime');
            if ($raw) {
                $secs = (int) floatval(trim(explode(' ', $raw)[0]));
                $days = floor($secs / 86400);
                $hours = floor(($secs % 86400) / 3600);
                $uptime = $days . 'd ' . $hours . 'h';
            }
        }
    } catch (\Throwable $e) {}

    $diskFree = @disk_free_space('/');
    $diskTotal = @disk_total_space('/');
    $diskUsed = ($diskTotal && $diskFree) ? $diskTotal - $diskFree : 0;
    $diskPct = ($diskTotal > 0) ? round(($diskUsed / $diskTotal) * 100, 1) : 0;
    $diskFreeGB = $diskFree ? round($diskFree / 1073741824, 1) : 'N/A';
    $diskTotalGB = $diskTotal ? round($diskTotal / 1073741824, 1) : 'N/A';

    $memoryLimit = ini_get('memory_limit');
    $memUsage = round(memory_get_usage(true) / 1048576, 1);

    $loadAvg = 'N/A';
    try {
        if (function_exists('sys_getloadavg')) {
            $la = @sys_getloadavg();
            if ($la !== false && is_array($la)) {
                $loadAvg = sprintf('%.2f, %.2f, %.2f', $la[0] ?? 0, $la[1] ?? 0, $la[2] ?? 0);
            }
        }
    } catch (\Throwable $e) {}

    $opcacheOn = function_exists('opcache_get_status') && @opcache_get_status() !== false;
    $debugMode = config('app.debug') ? 'Activado' : 'Desactivado';
    $cacheDriver = config('cache.default');
    $sessionDriver = config('session.driver');
    $queueDriver = config('queue.default');
    $mailDriver = config('mail.default');
    $appEnv = config('app.env');

    // === Cargar scan de actualizaciones (Sprint 139KK) ===
    $updates = null;
    $updatesAge = null;
    $updatesAgeDays = null;
    try {
        $upPath = storage_path('app/stack-updates.json');
        if (file_exists($upPath)) {
            $updates = json_decode(file_get_contents($upPath), true);
            $mtime = @filemtime($upPath);
            if ($mtime) {
                $diff = time() - $mtime;
                $updatesAgeDays = floor($diff / 86400);
                if ($diff < 3600) $updatesAge = floor($diff / 60) . ' min';
                elseif ($diff < 86400) $updatesAge = floor($diff / 3600) . ' h';
                else $updatesAge = $updatesAgeDays . ' d';
            }
        }
    } catch (\Throwable $e) {}

    // === Calcular advertencias (Sprint 139KK) ===
    $warnings = [];
    if ($debugMode === 'Activado' && $appEnv === 'production') {
        $warnings[] = ['level' => 'critical', 'msg' => 'Debug Mode está ACTIVADO en producción. Esto expone información sensible. Cambiar APP_DEBUG=false en .env de inmediato.'];
    }
    if ($updates) {
        $secCount = $updates['resumen']['security_total'] ?? 0;
        if ($secCount > 0) {
            $warnings[] = ['level' => 'critical', 'msg' => "Hay $secCount security advisory(s) sin parchar. Revisa la sección al final de este reporte."];
        }
        $patchCount = $updates['resumen']['patch'] ?? 0;
        if ($patchCount > 0) {
            $warnings[] = ['level' => 'info', 'msg' => "Hay $patchCount parche(s) seguro(s) disponible(s). Botón \"Aplicar parches seguros\" en el header."];
        }
        $majorCount = $updates['resumen']['major'] ?? 0;
        if ($majorCount > 0) {
            $warnings[] = ['level' => 'warn', 'msg' => "Hay $majorCount major upgrade(s) disponible(s). NO se pueden aplicar automáticamente — contienen breaking changes (ver tabla)."];
        }
        if ($updatesAgeDays > 7) {
            $warnings[] = ['level' => 'warn', 'msg' => "El último escaneo tiene $updatesAgeDays días. Recomendado: presiona \"Refrescar escaneo\" para datos frescos."];
        }
    } else {
        $warnings[] = ['level' => 'info', 'msg' => 'No hay escaneo previo. Presiona "Refrescar escaneo" en el header (puede tardar 30-60s la primera vez).'];
    }

    if ((float) $diskPct > 85) {
        $warnings[] = ['level' => 'warn', 'msg' => "Disco al $diskPct% de uso. Considera liberar espacio o expandir."];
    }
    if (!$opcacheOn) {
        $warnings[] = ['level' => 'warn', 'msg' => 'OPcache está desactivado. PHP funcionará pero más lento. Recomendado activar en php.ini.'];
    }
@endphp

@if(!empty($warnings))
    <div style="margin-bottom: 18px;">
        @foreach($warnings as $w)
            @php
                $bg = match($w['level']) {
                    'critical' => 'rgba(239, 68, 68, 0.15)',
                    'warn'     => 'rgba(245, 158, 11, 0.15)',
                    'info'     => 'rgba(59, 130, 246, 0.15)',
                    default    => 'rgba(107, 114, 128, 0.15)',
                };
                $border = match($w['level']) {
                    'critical' => '#ef4444',
                    'warn'     => '#f59e0b',
                    'info'     => '#3b82f6',
                    default    => '#6b7280',
                };
                $icon = match($w['level']) {
                    'critical' => '🔴',
                    'warn'     => '⚠',
                    'info'     => 'ℹ',
                    default    => '•',
                };
            @endphp
            <div style="background: {{ $bg }}; border-left: 4px solid {{ $border }}; padding: 12px 16px; border-radius: 6px; margin-bottom: 8px; color: #e5e7eb; font-size: 13px;">
                <strong>{{ $icon }}</strong>&nbsp; {{ $w['msg'] }}
            </div>
        @endforeach
    </div>
@endif

<div class="ms-grid">
    <div class="ms-card">
        <h3>PHP</h3>
        <div class="ms-val">{{ $phpVersion }}</div>
        <div class="ms-sub">Debug: {{ $debugMode === 'Activado' ? 'on' : 'off' }}</div>
    </div>
    <div class="ms-card">
        <h3>Laravel</h3>
        <div class="ms-val">{{ $laravelVersion }}</div>
    </div>
    <div class="ms-card">
        <h3>Base de Datos</h3>
        <div class="ms-val">{{ $dbTables }} tablas</div>
        <div class="ms-sub">{{ $dbSizeMB }} MB totales</div>
    </div>
    <div class="ms-card">
        <h3>Disco libre</h3>
        <div class="ms-val">{{ $diskFreeGB }} GB</div>
        <div class="ms-sub">de {{ $diskTotalGB }} GB totales</div>
    </div>
    <div class="ms-card">
        <h3>Memoria PHP</h3>
        <div class="ms-val">{{ $memUsage }} MB</div>
        <div class="ms-sub">Límite: {{ $memoryLimit }}</div>
    </div>
    <div class="ms-card">
        <h3>Load avg</h3>
        <div class="ms-val">{{ $loadAvg }}</div>
        <div class="ms-sub">1 / 5 / 15 min</div>
    </div>
    <div class="ms-card">
        <h3>Uptime</h3>
        <div class="ms-val">{{ $uptime }}</div>
    </div>
</div>

@if($updates)
<div class="ms-section">
    <h2>Actualizaciones disponibles</h2>

    @php
        $r = $updates['resumen'] ?? [];
    @endphp

    <div class="ms-update-grid">
        <div class="ms-update-card">
            <div class="num" style="color: {{ ($r['outdated_total'] ?? 0) > 0 ? '#f59e0b' : '#10b981' }};">{{ $r['outdated_total'] ?? 0 }}</div>
            <div class="lbl">Paquetes outdated</div>
        </div>
        <div class="ms-update-card">
            <div class="num" style="color: #10b981;">{{ $r['patch'] ?? 0 }}</div>
            <div class="lbl">Parches (seguros)</div>
        </div>
        <div class="ms-update-card">
            <div class="num" style="color: #f59e0b;">{{ $r['minor'] ?? 0 }}</div>
            <div class="lbl">Minors</div>
        </div>
        <div class="ms-update-card">
            <div class="num" style="color: #ef4444;">{{ $r['major'] ?? 0 }}</div>
            <div class="lbl">Majors (breaking)</div>
        </div>
        <div class="ms-update-card">
            <div class="num" style="color: {{ ($r['security_total'] ?? 0) > 0 ? '#dc2626' : '#10b981' }};">{{ $r['security_total'] ?? 0 }}</div>
            <div class="lbl">Security advisories</div>
        </div>
    </div>

    {{-- Paquetes principales del stack --}}
    <h2 style="font-size: 14px; margin-top: 22px;">Versiones del stack</h2>
    <table class="ms-table">
        <thead>
            <tr>
                <th>Componente</th>
                <th>Actual</th>
                <th>Latest stable</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['php', 'laravel', 'filament', 'livewire', 'tinker'] as $key)
                @php
                    $info = $updates[$key] ?? [];
                    $actual = $info['actual'] ?? '—';
                    $latest = $info['latest'] ?? '—';
                    $bump = $info['bump'] ?? null;
                    $label = ucfirst($key);
                    if ($key === 'php') $label = 'PHP';
                    if ($key === 'laravel') $label = 'Laravel framework';
                    if ($key === 'filament') $label = 'Filament';
                    if ($key === 'livewire') $label = 'Livewire';
                    if ($key === 'tinker') $label = 'Laravel Tinker';
                @endphp
                <tr>
                    <td><strong>{{ $label }}</strong></td>
                    <td>{{ $actual }}</td>
                    <td>{{ $latest }}</td>
                    <td>
                        @if($bump === 'none' || $actual === $latest)
                            <span class="ms-badge ms-badge-patch">Al día</span>
                        @elseif($bump === 'patch')
                            <span class="ms-badge ms-badge-patch">Parche disponible</span>
                        @elseif($bump === 'minor')
                            <span class="ms-badge ms-badge-minor">Minor disponible</span>
                        @elseif($bump === 'major')
                            <span class="ms-badge ms-badge-major">Major disponible</span>
                        @else
                            <span class="ms-badge ms-badge-unknown">N/A</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Tabla de outdated paquetes --}}
    @if(!empty($updates['outdated']))
        <h2 style="font-size: 14px; margin-top: 22px;">Paquetes Composer outdated</h2>
        <table class="ms-table">
            <thead>
                <tr>
                    <th>Paquete</th>
                    <th>Actual</th>
                    <th>Latest</th>
                    <th>Tipo</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($updates['outdated'] as $pkg)
                    @php
                        $bump = $pkg['bump'] ?? 'unknown';
                        $name = $pkg['name'] ?? '?';
                        $current = $pkg['version'] ?? '?';
                        $latest = $pkg['latest'] ?? '?';
                        $latestStatus = $pkg['latest-status'] ?? '';
                        $description = $pkg['description'] ?? '';
                    @endphp
                    <tr>
                        <td>
                            <a href="https://packagist.org/packages/{{ $name }}" target="_blank" class="ms-link">{{ $name }}</a>
                        </td>
                        <td>{{ $current }}</td>
                        <td>{{ $latest }}</td>
                        <td>
                            <span class="ms-badge ms-badge-{{ $bump }}">{{ ucfirst($bump) }}</span>
                        </td>
                        <td style="font-size: 12px; color: #9ca3af;">{{ $description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Security advisories --}}
    @if(!empty($updates['security']))
        <h2 style="font-size: 14px; margin-top: 22px; color: #ef4444;">⚠ Security advisories</h2>
        <table class="ms-table">
            <thead>
                <tr>
                    <th>Paquete</th>
                    <th>CVE</th>
                    <th>Severidad</th>
                    <th>Título</th>
                    <th>Reportado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($updates['security'] as $adv)
                    <tr>
                        <td><strong>{{ $adv['paquete'] ?? '?' }}</strong></td>
                        <td>
                            @if(!empty($adv['link']))
                                <a href="{{ $adv['link'] }}" target="_blank" class="ms-link">{{ $adv['cve'] ?? 'ver' }}</a>
                            @else
                                {{ $adv['cve'] ?? '—' }}
                            @endif
                        </td>
                        <td>
                            <span class="ms-badge ms-badge-{{ strtolower($adv['severidad'] ?? 'unknown') }}">{{ $adv['severidad'] ?? '?' }}</span>
                        </td>
                        <td style="max-width: 400px;">{{ $adv['titulo'] ?? '' }}</td>
                        <td style="font-size: 11px; color: #9ca3af;">{{ \Illuminate\Support\Str::limit($adv['reportado'] ?? '', 10, '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="ms-meta">
        Último escaneo: {{ $updatesAge ? "hace $updatesAge" : 'desconocido' }}
        @if(!empty($updates['scan_at']))
            ({{ $updates['scan_at'] }})
        @endif
        — Para refrescar manualmente, ejecutar <code>php artisan stack:check-updates</code> en la consola del servidor.
    </div>
</div>
@else
<div class="ms-section">
    <h2>Actualizaciones disponibles</h2>
    <div class="ms-empty">
        Aún no se ha ejecutado el escaneo. Ejecuta <code>php artisan stack:check-updates</code> en la consola del servidor para generar el reporte.
        Después de eso, esta sección mostrará paquetes outdated, security advisories y recomendaciones de upgrade.
    </div>
</div>
@endif

<div class="ms-section">
    <h2>Disco</h2>
    <div class="ms-row">
        <span class="ms-label">Espacio Usado</span>
        <span class="ms-value">{{ $diskPct }}% de {{ $diskTotalGB }} GB</span>
    </div>
    <div class="ms-bar-bg"><div class="ms-bar-fill" style="width: {{ min($diskPct, 100) }}%"></div></div>
    <div class="ms-row" style="margin-top: 8px;">
        <span class="ms-label">Espacio Libre</span>
        <span class="ms-value">{{ $diskFreeGB }} GB</span>
    </div>
</div>

<div class="ms-section">
    <h2>Memoria y Carga</h2>
    <div class="ms-row">
        <span class="ms-label">Memoria PHP Usada</span>
        <span class="ms-value">{{ $memUsage }} MB</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Límite de Memoria</span>
        <span class="ms-value">{{ $memoryLimit }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Load Average</span>
        <span class="ms-value">{{ $loadAvg }}</span>
    </div>
</div>

<div class="ms-section">
    <h2>Entorno de Aplicación</h2>
    <div class="ms-row">
        <span class="ms-label">Entorno</span>
        <span class="ms-value">{{ $appEnv }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Debug Mode</span>
        <span class="ms-value {{ $debugMode === 'Activado' ? 'ms-warn' : 'ms-ok' }}">{{ $debugMode }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Cache Driver</span>
        <span class="ms-value">{{ $cacheDriver }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Session Driver</span>
        <span class="ms-value">{{ $sessionDriver }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Queue Driver</span>
        <span class="ms-value">{{ $queueDriver }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">Mail Driver</span>
        <span class="ms-value">{{ $mailDriver }}</span>
    </div>
    <div class="ms-row">
        <span class="ms-label">OPcache</span>
        <span class="ms-value {{ $opcacheOn ? 'ms-ok' : 'ms-warn' }}">{{ $opcacheOn ? 'Activado' : 'Desactivado' }}</span>
    </div>
</div>
</x-filament-panels::page>
