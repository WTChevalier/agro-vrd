<?php
namespace App\Filament\Widgets;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CatalogoStatsWidget extends BaseWidget {
    protected ?string $heading = 'Catálogo · SazónRD';
    protected static ?int $sort = 1;
    protected function getStats(): array {
        try {
            $platos = DB::table('platos')->count();
            $restaurantes = DB::table('restaurantes')->count();
            $pedidos = DB::table('pedidos')->count();
            $traducciones = DB::table('traducciones_contenido')->count();
        } catch (\Throwable) {
            $platos = $restaurantes = $pedidos = $traducciones = 0;
        }
        return [
            Stat::make('Platos', $platos)->color('warning'),
            Stat::make('Restaurantes', $restaurantes)->color('success'),
            Stat::make('Pedidos', $pedidos)->color('primary'),
            Stat::make('Traducciones', $traducciones)->color('info'),
        ];
    }
}
