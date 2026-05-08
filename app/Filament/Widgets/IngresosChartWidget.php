<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class IngresosChartWidget extends ChartWidget
{
    protected ?string $heading = 'Ingresos - Última Semana';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D');

            try {
                $total = \App\Models\Pedido::whereDate('created_at', $date)->sum('total') ?? 0;
                $data[] = $total;
            } catch (\Exception $e) {
                $data[] = 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos RD$',
                    'data' => $data,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => '#10B981',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
