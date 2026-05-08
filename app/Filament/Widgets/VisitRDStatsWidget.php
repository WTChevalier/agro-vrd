<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use PDO;
use Exception;

class VisitRDStatsWidget extends BaseWidget
{
    protected ?string $heading = '🏝️ Visit República Dominicana - Turismo';
    protected static ?int $sort = 15;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=visi_rd;charset=utf8mb4",
                "visi_rd",
                "#cllOzgkN6o-3Ho7",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $atracciones = (int) $pdo->query("SELECT COUNT(*) FROM attractions")->fetchColumn();
            $playas = (int) $pdo->query("SELECT COUNT(*) FROM beaches")->fetchColumn();
            $eventos = (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
            $rutas = (int) $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();

            return [
                Stat::make('Atracciones', number_format($atracciones))
                    ->description('Lugares turísticos')
                    ->descriptionIcon('heroicon-m-map-pin')
                    ->color('success')
                    ->url('https://visitrepublicadominicana.com/admin/attractions', shouldOpenInNewTab: true),
                Stat::make('Playas', number_format($playas))
                    ->description('Destinos de playa')
                    ->descriptionIcon('heroicon-m-sun')
                    ->color('info'),
                Stat::make('Eventos', number_format($eventos))
                    ->description('Próximos eventos')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('warning'),
                Stat::make('Rutas', number_format($rutas))
                    ->description('Rutas turísticas')
                    ->descriptionIcon('heroicon-m-map')
                    ->color('primary'),
            ];
        } catch (Exception $e) {
            return [
                Stat::make('Visit RD', 'Ir al Panel')
                    ->description('Click para abrir')
                    ->descriptionIcon('heroicon-m-arrow-top-right-on-square')
                    ->color('primary')
                    ->url('https://visitrepublicadominicana.com/admin', shouldOpenInNewTab: true),
            ];
        }
    }
}
