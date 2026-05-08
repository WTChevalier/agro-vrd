<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenGeneralWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $certificaciones = 0;
        $suscripciones = 0;
        $planes = 0;
        $repartidores = 0;

        try {
            $certificaciones = \App\Models\Certificacion::count();
        } catch (\Exception $e) {}

        try {
            $suscripciones = \App\Models\Suscripcion::count();
        } catch (\Exception $e) {}

        try {
            $planes = \App\Models\Plan::count();
        } catch (\Exception $e) {}

        try {
            $repartidores = \App\Models\Repartidor::count();
        } catch (\Exception $e) {}

        return [
            Stat::make('Certificaciones', $certificaciones)
                ->description('Otorgadas')
                ->color('success'),

            Stat::make('Suscripciones', $suscripciones)
                ->description('Activas')
                ->color('info'),

            Stat::make('Planes', $planes)
                ->description('Disponibles')
                ->color('warning'),

            Stat::make('Repartidores', $repartidores)
                ->description('Registrados')
                ->color('danger'),
        ];
    }
}
