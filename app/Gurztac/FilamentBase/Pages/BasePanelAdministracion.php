<?php

/**
 * Sprint 139AA — BasePanelAdministracion compartido del ecosistema.
 *
 * Reemplaza el "Escritorio" default de Filament con "Panel de Administración"
 * y ofrece subtítulo dinámico con saludo según hora del día.
 *
 * Cada app tenant: crea su Dashboard.php que extiende BasePanelAdministracion
 * y solo sobrescribe getWidgets() para registrar sus widgets propios.
 *
 * Ejemplo de uso en una app:
 * ```php
 * namespace App\Filament\Pages;
 *
 * use App\Gurztac\FilamentBase\Pages\BasePanelAdministracion;
 *
 * class Dashboard extends BasePanelAdministracion
 * {
 *     public function getWidgets(): array
 *     {
 *         return [
 *             \App\Filament\Widgets\StatsOverviewWidget::class,
 *             \App\Filament\Widgets\PedidosChartWidget::class,
 *         ];
 *     }
 * }
 * ```
 *
 * Última revisión: 2026-04-28 — WT Chevalier (Founder)
 */

namespace App\Gurztac\FilamentBase\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class BasePanelAdministracion extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Panel de Administración';
    protected static ?string $navigationLabel = 'Panel de Administración';
    protected static ?int $navigationSort = -100;

    public function getHeading(): string
    {
        return 'Panel de Administración';
    }

    /**
     * Subtítulo dinámico con saludo según hora + nombre del usuario.
     * Cada app puede sobrescribir para personalizar más.
     */
    public function getSubheading(): ?string
    {
        $hora = now()->format('H');
        $saludo = match (true) {
            $hora < 12 => 'Buenos días',
            $hora < 19 => 'Buenas tardes',
            default    => 'Buenas noches',
        };

        $usuario = auth()->user();
        $nombre = $usuario?->name
            ?? $usuario?->nombre_preferido
            ?? $usuario?->nombre_completo
            ?? 'admin';

        return "{$saludo}, {$nombre}.";
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
