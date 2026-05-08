<?php



namespace App\Filament\Pages;



use Filament\Pages\Dashboard as BaseDashboard;



/* Sprint 139Z: rebrand a Panel de Administración */
class Dashboard extends BaseDashboard

{

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Panel de Administración';

    protected static ?string $navigationLabel = 'Panel de Administración';

    protected static ?int $navigationSort = -2;



    public function getWidgets(): array

    {

        return [

            \App\Filament\Widgets\StatsOverviewWidget::class,

            \App\Filament\Widgets\ResumenGeneralWidget::class,

            \App\Filament\Widgets\PedidosChartWidget::class,

            \App\Filament\Widgets\IngresosChartWidget::class,

        ];

    }



    public function getColumns(): int|array

    {

        return 2;

    }

}

