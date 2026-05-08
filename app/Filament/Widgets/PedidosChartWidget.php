<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class PedidosChartWidget extends ChartWidget
{
    protected ?string $heading = 'Pedidos - Última Semana';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D');

            try {
                $count = \App\Models\Pedido::whereDate('created_at', $date)->count();
                $data[] = $count;
            } catch (\Exception $e) {
                $data[] = 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pedidos',
                    'data' => $data,
                    'borderColor' => '#E63946',
                    'backgroundColor' => 'rgba(230, 57, 70, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
