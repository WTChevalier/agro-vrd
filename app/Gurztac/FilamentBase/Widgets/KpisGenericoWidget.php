<?php

/**
 * Sprint 139AA — Widget genérico de KPIs con sparklines animados.
 *
 * Cada app del ecosistema puede:
 *   1. Registrar este widget directamente en su panel para tener KPIs default
 *      (visitantes, usuarios, registros, actividad reciente).
 *   2. Crear su propia clase que extiende esta y sobrescribe getStats() con
 *      KPIs específicos de su negocio (pedidos para SazónRD, eventos para
 *      GurzTicket, miembros para ExerFitness, etc.).
 *
 * Características:
 *   - 4 stats con sparkline de últimos 7 días (animación de líneas)
 *   - Emojis temáticos para reconocimiento visual instantáneo
 *   - Cache 5 min para no saturar DB en cada render
 *   - Fail-soft: si una tabla no existe, retorna 0 en lugar de tronar
 *   - Compatible con cualquier app que tenga `users` table
 *
 * Configurable via método tablasMonitoreadas() — cada app sobrescribe para
 * apuntar a sus tablas específicas.
 *
 * Última revisión: 2026-04-28 — WT Chevalier (Founder)
 */

namespace App\Gurztac\FilamentBase\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KpisGenericoWidget extends BaseWidget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    /**
     * Override en cada app para apuntar a sus tablas.
     *
     * Estructura esperada:
     *   [
     *     'titulo'       => string (con emoji),
     *     'tabla'        => string (nombre de la tabla),
     *     'descripcion'  => string,
     *     'icono'        => string (heroicon),
     *     'color'        => 'primary'|'info'|'success'|'warning'|'danger',
     *     'condicion'    => ?\Closure (modificador del query, opcional),
     *   ]
     *
     * Default: 4 stats genéricos basados en `users` y registros recientes.
     */
    protected function tablasMonitoreadas(): array
    {
        return [
            [
                'titulo'      => 'Usuarios totales 👥',
                'tabla'       => 'users',
                'descripcion' => 'Total registrados',
                'icono'       => 'heroicon-o-users',
                'color'       => 'primary',
            ],
            [
                'titulo'      => 'Nuevos hoy ✨',
                'tabla'       => 'users',
                'descripcion' => 'Registros del día',
                'icono'       => 'heroicon-o-user-plus',
                'color'       => 'success',
                'condicion'   => fn ($q) => $q->whereDate('created_at', today()),
            ],
            [
                'titulo'      => 'Activos esta semana 🔥',
                'tabla'       => 'users',
                'descripcion' => 'Con actividad 7 días',
                'icono'       => 'heroicon-o-fire',
                'color'       => 'warning',
                'condicion'   => fn ($q) => $q->where('updated_at', '>=', now()->subDays(7)),
            ],
            [
                'titulo'      => 'Crecimiento 30d 📈',
                'tabla'       => 'users',
                'descripcion' => 'Nuevos en último mes',
                'icono'       => 'heroicon-o-arrow-trending-up',
                'color'       => 'info',
                'condicion'   => fn ($q) => $q->where('created_at', '>=', now()->subDays(30)),
            ],
        ];
    }

    protected function getStats(): array
    {
        $cacheKey = 'kpis_generico_' . request()->getHost();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $stats = [];
            foreach ($this->tablasMonitoreadas() as $config) {
                $stats[] = $this->buildStat($config);
            }
            return $stats;
        });
    }

    private function buildStat(array $config): Stat
    {
        $valor = $this->safeCount($config['tabla'], $config['condicion'] ?? null);
        $sparkline = $this->buildSparkline($config['tabla'], $config['condicion'] ?? null, 7);

        return Stat::make($config['titulo'], number_format($valor))
            ->description($config['descripcion'])
            ->color($config['color'] ?? 'primary')
            ->chart($sparkline)
            ->icon($config['icono'] ?? 'heroicon-o-chart-bar');
    }

    /**
     * Cuenta filas en una tabla aplicando un closure opcional.
     * Si la tabla no existe o falla, retorna 0 (fail-soft).
     */
    private function safeCount(string $tabla, ?\Closure $modifier): int
    {
        try {
            $q = DB::table($tabla);
            if ($modifier) {
                $modifier($q);
            }
            return (int) $q->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Sparkline de últimos N días: count de filas creadas cada día.
     */
    private function buildSparkline(string $tabla, ?\Closure $modifier, int $dias): array
    {
        try {
            $datos = [];
            for ($i = $dias - 1; $i >= 0; $i--) {
                $inicio = now()->subDays($i)->startOfDay();
                $fin    = now()->subDays($i)->endOfDay();
                $q = DB::table($tabla)->whereBetween('created_at', [$inicio, $fin]);
                if ($modifier) {
                    // El modifier es para el count total, no para el sparkline.
                    // El sparkline siempre muestra crecimiento diario.
                }
                $datos[] = (int) $q->count();
            }
            return $datos;
        } catch (\Throwable) {
            return array_fill(0, $dias, 0);
        }
    }
}
