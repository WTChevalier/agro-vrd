<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pedidosHoy = 0;
        $ingresos = 0;
        $restaurantes = 0;
        $usuarios = 0;

        try {
            $pedidosHoy = \App\Models\Pedido::whereDate('created_at', today())->count();
        } catch (\Exception $e) {}

        try {
            $ingresos = \App\Models\Pedido::whereDate('created_at', today())->sum('total') ?? 0;
        } catch (\Exception $e) {}

        try {
            $restaurantes = \App\Models\Restaurante::count();
        } catch (\Exception $e) {}

        try {
            $usuarios = \App\Models\User::count();
        } catch (\Exception $e) {}

        return [
            Stat::make('Pedidos Hoy', $pedidosHoy)
                ->description('Total del día')
                ->color('danger'),

            Stat::make('Ingresos Hoy', 'RD$ ' . number_format($ingresos, 0, ',', '.'))
                ->description('Ventas del día')
                ->color('success'),

            Stat::make('Restaurantes', $restaurantes)
                ->description('Registrados')
                ->color('warning'),

            Stat::make('Usuarios', $usuarios)
                ->description('Total')
                ->color('info'),
        ];
    }
}
